<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a supervisor
requireUserType('supervisor');

// Get supervisor details
$supervisor = getSupervisorDetails($conn, $_SESSION['user_id']);

// Get student ID from URL
$student_id = isset($_GET['student_id']) ? (int)$_GET['student_id'] : 0;
$visit_number = isset($_GET['visit']) ? (int)$_GET['visit'] : 0;

// If no specific student, show list of students
if ($student_id == 0) {
    // Get assigned students
    $stmt = $conn->prepare("SELECT s.*, o.name as organization_name, 
                           COUNT(ua.assessment_id) as assessment_count 
                        FROM students s 
                        JOIN student_supervisor ss ON s.student_id = ss.student_id 
                        LEFT JOIN organizations o ON s.organization_id = o.organization_id
                        LEFT JOIN university_assessments ua ON s.student_id = ua.student_id
                        WHERE ss.supervisor_id = ? AND s.attachment_status = 'ongoing'
                        GROUP BY s.student_id
                        HAVING assessment_count < 2
                        ORDER BY assessment_count ASC, s.full_name ASC");
    $stmt->bind_param("i", $supervisor['supervisor_id']);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Include header
    $page_title = "Assess Students";
    include '../../includes/header.php';
    ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Students Pending Assessment</h5>
                </div>
                <div class="card-body">
                    <?php if(empty($students)): ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle mr-2"></i> You don't have any students that need assessment at this time.
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fa fa-info-circle mr-2"></i> Each student requires two assessment visits during their attachment period.
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                        <th>Registration No.</th>
                                        <th>Organization</th>
                                        <th>Pending Visit</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($students as $student): ?>
                                        <tr>
                                            <td><?php echo $student['full_name']; ?></td>
                                            <td><?php echo $student['registration_number']; ?></td>
                                            <td><?php echo $student['organization_name'] ?? 'Not Assigned'; ?></td>
                                            <td>Visit #<?php echo $student['assessment_count'] + 1; ?></td>
                                            <td>
                                                <a href="assess_students.php?student_id=<?php echo $student['student_id']; ?>&visit=<?php echo $student['assessment_count'] + 1; ?>" class="btn btn-primary btn-sm">
                                                    <i class="fa fa-clipboard-check"></i> Conduct Assessment
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
    $stmt = $conn->prepare("SELECT s.*, o.name as organization_name, o.location as organization_location 
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
        header("Location: assess_students.php");
        exit();
    }
    
    $student = $result->fetch_assoc();
    
    // Check if organization is assigned
    if(!$student['organization_id']) {
        // Redirect back with error
        header("Location: assess_students.php?error=no_organization");
        exit();
    }
    
    // Check previous assessments
    $stmt = $conn->prepare("SELECT * FROM university_assessments WHERE student_id = ? ORDER BY visit_number");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $previous_assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Determine which visit this is (1 or 2)
    if($visit_number == 0) {
        $visit_number = count($previous_assessments) + 1;
        if($visit_number > 2) {
            // Redirect back with error - already did 2 visits
            header("Location: assess_students.php?error=max_visits");
            exit();
        }
    }
    
    // Process form submission
    $success = $error = '';
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Form validation
        $visit_date = cleanInput($_POST['visit_date']);
        $progress = (int)$_POST['progress'];
        $attendance = (int)$_POST['attendance'];
        $technical_skills = (int)$_POST['technical_skills'];
        $presentation = (int)$_POST['presentation'];
        $remarks = cleanInput($_POST['remarks']);
        
        // Calculate total score
        $total_score = $progress + $attendance + $technical_skills + $presentation;
        
        // Validate inputs
        if (empty($visit_date) || empty($remarks)) {
            $error = "All fields are required";
        } elseif ($progress < 0 || $progress > 25 || $attendance < 0 || $attendance > 25 || 
                 $technical_skills < 0 || $technical_skills > 25 || $presentation < 0 || $presentation > 25) {
            $error = "Scores must be between 0 and 25";
        } else {
            // Insert assessment
            $stmt = $conn->prepare("INSERT INTO university_assessments 
                                 (student_id, supervisor_id, visit_number, visit_date, progress, attendance, 
                                  technical_skills, presentation, remarks, total_score) 
                                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiisiiiiis", $student_id, $supervisor['supervisor_id'], $visit_number,
                             $visit_date, $progress, $attendance, $technical_skills, $presentation, $remarks, $total_score);
            
            if ($stmt->execute()) {
                $success = "Assessment for Visit #{$visit_number} submitted successfully";
                
                // Reset form data
                unset($visit_date, $progress, $attendance, $technical_skills, $presentation, $remarks);
                
                // If this was the second visit and student is in ongoing status, mark student as completed
                if($visit_number == 2 && $student['attachment_status'] == 'ongoing') {
                    $stmt = $conn->prepare("UPDATE students SET attachment_status = 'completed' WHERE student_id = ?");
                    $stmt->bind_param("i", $student_id);
                    $stmt->execute();
                }
                
                // Refresh previous assessments
                $stmt = $conn->prepare("SELECT * FROM university_assessments WHERE student_id = ? ORDER BY visit_number");
                $stmt->bind_param("i", $student_id);
                $stmt->execute();
                $previous_assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                
                // Update visit number
                $visit_number = count($previous_assessments) + 1;
                if($visit_number > 2) {
                    // Redirect to assessments list after submission
                    header("Location: assess_students.php?success=assessment_submitted");
                    exit();
                }
            } else {
                $error = "Error submitting assessment: " . $conn->error;
            }
        }
    }
    
    // Include header
    $page_title = "Student Assessment Form";
    include '../../includes/header.php';
    ?>
    
    <div class="row">
        <div class="col-lg-12">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Assessment Form: Visit #<?php echo $visit_number; ?></h5>
                    <a href="assess_students.php" class="btn btn-light btn-sm">
                        <i class="fa fa-arrow-left"></i> Back to Students
                    </a>
                </div>
                <div class="card-body">
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Student Information</h6>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo $student['full_name']; ?></p>
                                    <p class="mb-1"><strong>Registration Number:</strong> <?php echo $student['registration_number']; ?></p>
                                    <p class="mb-1"><strong>Organization:</strong> <?php echo $student['organization_name']; ?></p>
                                    <p class="mb-0"><strong>Location:</strong> <?php echo $student['organization_location']; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Previous Assessments</h6>
                                    <?php if (empty($previous_assessments)): ?>
                                        <p class="text-muted">No previous assessments.</p>
                                    <?php else: ?>
                                        <?php foreach($previous_assessments as $assessment): ?>
                                            <div class="mb-2">
                                                <p class="mb-1"><strong>Visit #<?php echo $assessment['visit_number']; ?>:</strong> 
                                                    <?php echo date('M d, Y', strtotime($assessment['visit_date'])); ?>
                                                </p>
                                                <p class="mb-1"><strong>Score:</strong> <?php echo $assessment['total_score']; ?>/100</p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($visit_number <= 2): ?>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="visit_date">Visit Date</label>
                                <input type="date" class="form-control" id="visit_date" name="visit_date" value="<?php echo $visit_date ?? date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="progress">Progress (0-25)</label>
                                        <input type="number" class="form-control" id="progress" name="progress" min="0" max="25" value="<?php echo $progress ?? ''; ?>" required>
                                        <small class="form-text text-muted">Assess student's progress on assigned tasks.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="attendance">Attendance (0-25)</label>
                                        <input type="number" class="form-control" id="attendance" name="attendance" min="0" max="25" value="<?php echo $attendance ?? ''; ?>" required>
                                        <small class="form-text text-muted">Assess student's punctuality and attendance.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="technical_skills">Technical Skills (0-25)</label>
                                        <input type="number" class="form-control" id="technical_skills" name="technical_skills" min="0" max="25" value="<?php echo $technical_skills ?? ''; ?>" required>
                                        <small class="form-text text-muted">Assess student's technical competency.</small>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="presentation">Communication (0-25)</label>
                                        <input type="number" class="form-control" id="presentation" name="presentation" min="0" max="25" value="<?php echo $presentation ?? ''; ?>" required>
                                        <small class="form-text text-muted">Assess student's communication skills.</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="remarks">Assessment Remarks</label>
                                <textarea class="form-control" id="remarks" name="remarks" rows="5" required><?php echo $remarks ?? ''; ?></textarea>
                                <small class="form-text text-muted">Provide detailed feedback on student's performance, areas of improvement, and recommendations.</small>
                            </div>
                            
                            <div class="text-right">
                                <button type="submit" class="btn btn-primary">Submit Assessment</button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            You have already conducted two assessments for this student. No more assessments are required.
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