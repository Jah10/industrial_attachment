<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a supervisor
requireUserType('supervisor');

// Get supervisor details
$supervisor = getSupervisorDetails($conn, $_SESSION['user_id']);

// Check if a specific student is selected
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;

// If no specific student, get all assigned students
if ($student_id == 0) {
    $stmt = $conn->prepare("SELECT s.student_id, s.full_name, s.registration_number
                         FROM students s 
                         JOIN student_supervisor ss ON s.student_id = ss.student_id
                         WHERE ss.supervisor_id = ?");
    $stmt->bind_param("i", $supervisor['supervisor_id']);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Include header
    $page_title = "View Student Logbooks";
    include '../../includes/header.php';
    ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Select Student to View Logbooks</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($students)): ?>
                        <div class="alert alert-info">You don't have any students assigned to you yet.</div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Registration No.</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['full_name']; ?></td>
                                            <td><?php echo $student['registration_number']; ?></td>
                                            <td>
                                                <a href="view_logbooks.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fa fa-book"></i> View Logbooks
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
<?php
} else {
    // Get student details
    $stmt = $conn->prepare("SELECT s.*, o.name as organization_name 
                         FROM students s 
                         LEFT JOIN organizations o ON s.organization_id = o.organization_id
                         JOIN student_supervisor ss ON s.student_id = ss.student_id
                         WHERE s.student_id = ? AND ss.supervisor_id = ?");
    $stmt->bind_param("ii", $student_id, $supervisor['supervisor_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Check if student exists and is assigned to this supervisor
    if($result->num_rows == 0) {
        // Redirect back to the student list
        header("Location: view_logbooks.php");
        exit();
    }
    
    $student = $result->fetch_assoc();
    
    // Get student's logbooks
    $stmt = $conn->prepare("SELECT * FROM logbooks WHERE student_id = ? ORDER BY week_number DESC");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $logbooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Include header
    $page_title = "Student Logbooks";
    include '../../includes/header.php';
    ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Logbook Entries for <?php echo $student['full_name']; ?> (<?php echo $student['registration_number']; ?>)</h5>
                    <a href="view_logbooks.php" class="btn btn-light btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Students
                    </a>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Student Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo $student['full_name']; ?></p>
                                    <p class="mb-1"><strong>Registration Number:</strong> <?php echo $student['registration_number']; ?></p>
                                    <p class="mb-1"><strong>Organization:</strong> <?php echo $student['organization_name'] ?? 'Not Assigned'; ?></p>
                                    <p class="mb-0"><strong>Status:</strong> 
                                        <?php 
                                        $status_badges = [
                                            'pending' => 'badge-warning',
                                            'matched' => 'badge-info',
                                            'ongoing' => 'badge-primary',
                                            'completed' => 'badge-success'
                                        ];
                                        $badge_class = $status_badges[$student['attachment_status']] ?? 'badge-secondary';
                                        ?>
                                        <span class="badge <?php echo $badge_class; ?>"><?php echo ucfirst($student['attachment_status']); ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Logbook Summary</h6>
                                    <p class="mb-1"><strong>Total Entries:</strong> <?php echo count($logbooks); ?></p>
                                    <p class="mb-1"><strong>Last Updated:</strong> 
                                        <?php 
                                        echo !empty($logbooks) ? date('M d, Y', strtotime($logbooks[0]['submission_date'])) : 'N/A'; 
                                        ?>
                                    </p>
                                    <a href="assess_students.php?student_id=<?php echo $student_id; ?>" class="btn btn-success mt-2">
                                        <i class="fa fa-clipboard-check"></i> Assess Student
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($logbooks)): ?>
                        <div class="alert alert-info">
                            This student hasn't submitted any logbook entries yet.
                        </div>
                    <?php else: ?>
                        <div class="accordion" id="logbookAccordion">
                            <?php foreach ($logbooks as $index => $logbook): ?>
                                <div class="card mb-2">
                                    <div class="card-header" id="heading<?php echo $logbook['logbook_id']; ?>">
                                        <h2 class="mb-0">
                                            <button class="btn btn-link btn-block text-left d-flex justify-content-between" type="button" data-toggle="collapse" data-target="#collapse<?php echo $logbook['logbook_id']; ?>" aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $logbook['logbook_id']; ?>">
                                                <span>Week <?php echo $logbook['week_number']; ?> (<?php echo date('M d', strtotime($logbook['start_date'])); ?> - <?php echo date('M d, Y', strtotime($logbook['end_date'])); ?>)</span>
                                                <span class="text-muted small">Submitted: <?php echo date('M d, Y', strtotime($logbook['submission_date'])); ?></span>
                                            </button>
                                        </h2>
                                    </div>

                                    <div id="collapse<?php echo $logbook['logbook_id']; ?>" class="collapse <?php echo ($index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $logbook['logbook_id']; ?>" data-parent="#logbookAccordion">
                                        <div class="card-body">
                                            <h6 class="font-weight-bold">Activities</h6>
                                            <p><?php echo nl2br($logbook['activities']); ?></p>
                                            
                                            <h6 class="font-weight-bold">Challenges</h6>
                                            <p><?php echo !empty($logbook['challenges']) ? nl2br($logbook['challenges']) : 'None reported'; ?></p>
                                            
                                            <h6 class="font-weight-bold">Solutions/Lessons Learned</h6>
                                            <p><?php echo !empty($logbook['solutions']) ? nl2br($logbook['solutions']) : 'None reported'; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
<?php
}

include '../../includes/footer.php';
?>