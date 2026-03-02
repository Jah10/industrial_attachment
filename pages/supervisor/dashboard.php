<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a supervisor
requireUserType('supervisor');

// Get supervisor details
$supervisor = getSupervisorDetails($conn, $_SESSION['user_id']);

// Get assigned students
$stmt = $conn->prepare("SELECT s.*, u.email, ss.assignment_id 
                        FROM students s 
                        JOIN users u ON s.user_id = u.user_id
                        JOIN student_supervisor ss ON s.student_id = ss.student_id 
                        WHERE ss.supervisor_id = ?");
$stmt->bind_param("i", $supervisor['supervisor_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get upcoming assessments (students who need to be assessed)
$stmt = $conn->prepare("SELECT s.student_id, s.full_name, s.registration_number, o.name as organization_name, 
                         COUNT(ua.assessment_id) as assessment_count
                        FROM students s 
                        JOIN student_supervisor ss ON s.student_id = ss.student_id
                        LEFT JOIN organizations o ON s.organization_id = o.organization_id
                        LEFT JOIN university_assessments ua ON s.student_id = ua.student_id
                        WHERE ss.supervisor_id = ? AND s.attachment_status = 'ongoing'
                        GROUP BY s.student_id
                        HAVING assessment_count < 2
                        ORDER BY assessment_count ASC");
$stmt->bind_param("i", $supervisor['supervisor_id']);
$stmt->execute();
$pending_assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get reminders
$reminders = getReminders($conn, 'supervisor');

// Include header
$page_title = "Supervisor Dashboard";
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Assigned Students</h5>
            </div>
            <div class="card-body">
                <?php if(empty($students)): ?>
                    <div class="alert alert-info">
                        You don't have any students assigned to you yet. The coordinator will assign students to you.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Registration No.</th>
                                    <th>Organization</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['full_name']; ?></td>
                                        <td><?php echo $student['registration_number']; ?></td>
                                        <td>
                                            <?php 
                                            if($student['organization_id']) {
                                                $stmt = $conn->prepare("SELECT name FROM organizations WHERE organization_id = ?");
                                                $stmt->bind_param("i", $student['organization_id']);
                                                $stmt->execute();
                                                $org = $stmt->get_result()->fetch_assoc();
                                                echo $org['name'];
                                            } else {
                                                echo '<span class="badge badge-warning">Not Assigned</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
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
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="view_logbooks.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fa fa-book"></i> Logbooks
                                                </a>
                                                <a href="assess_students.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fa fa-clipboard-check"></i> Assess
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Pending Assessments</h5>
            </div>
            <div class="card-body">
                <?php if(empty($pending_assessments)): ?>
                    <div class="alert alert-success">
                        <i class="fa fa-check-circle mr-2"></i> You have no pending assessments at this time.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Student Name</th>
                                    <th>Organization</th>
                                    <th>Assessment Visit</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pending_assessments as $assessment): ?>
                                    <tr>
                                        <td><?php echo $assessment['full_name']; ?></td>
                                        <td><?php echo $assessment['organization_name'] ?? 'Not Assigned'; ?></td>
                                        <td>Visit #<?php echo $assessment['assessment_count'] + 1; ?></td>
                                        <td>
                                            <a href="assess_students.php?student_id=<?php echo $assessment['student_id']; ?>&visit=<?php echo $assessment['assessment_count'] + 1; ?>" class="btn btn-sm btn-primary">
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
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">My Profile</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <div class="list-group-item">
                        <h6 class="mb-1">Name</h6>
                        <p class="mb-0"><?php echo $supervisor['full_name']; ?></p>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Department</h6>
                        <p class="mb-0"><?php echo $supervisor['department']; ?></p>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Phone</h6>
                        <p class="mb-0"><?php echo $supervisor['phone']; ?></p>
                    </div>
                    <div class="list-group-item">
                        <h6 class="mb-1">Total Assigned Students</h6>
                        <p class="mb-0"><?php echo count($students); ?></p>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="profile.php" class="btn btn-sm btn-outline-primary btn-block">
                        <i class="fa fa-user-edit"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">Reminders</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($reminders)): ?>
                    <div class="list-group">
                        <?php foreach ($reminders as $reminder): ?>
                            <div class="list-group-item">
                                <h6 class="mb-1"><?php echo $reminder['title']; ?></h6>
                                <p class="mb-1"><?php echo $reminder['description']; ?></p>
                                <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($reminder['due_date'])); ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted mb-0">No active reminders.</p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="view_logbooks.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-book mr-2"></i> View Student Logbooks
                    </a>
                    <a href="assess_students.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-clipboard-check mr-2"></i> Assess Students
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-user mr-2"></i> Update Profile
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>