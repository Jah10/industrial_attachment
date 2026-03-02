<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a student
requireUserType('student');

// Get student details
$student = getStudentDetails($conn, $_SESSION['user_id']);

// Get reminders
$reminders = getReminders($conn, 'student');

// Get logbook submissions
$stmt = $conn->prepare("SELECT * FROM logbooks WHERE student_id = ? ORDER BY week_number DESC");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$logbooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get final report status
$stmt = $conn->prepare("SELECT * FROM final_reports WHERE student_id = ?");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$final_report = $stmt->get_result()->fetch_assoc();

// Get attachment status
$attachment_status = $student['attachment_status'];

// Get organization details if student is matched
$organization = null;
if ($student['organization_id']) {
    $stmt = $conn->prepare("SELECT * FROM organizations WHERE organization_id = ?");
    $stmt->bind_param("i", $student['organization_id']);
    $stmt->execute();
    $organization = $stmt->get_result()->fetch_assoc();
}

// Get supervisor details if assigned
$supervisor = null;
$stmt = $conn->prepare("SELECT s.* FROM supervisors s 
                         JOIN student_supervisor ss ON s.supervisor_id = ss.supervisor_id 
                         WHERE ss.student_id = ?");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$supervisor = $stmt->get_result()->fetch_assoc();

// Get assessments
$industrial_assessment = null;
$stmt = $conn->prepare("SELECT * FROM industrial_assessments WHERE student_id = ?");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$industrial_assessment = $stmt->get_result()->fetch_assoc();

$university_assessments = array();
$stmt = $conn->prepare("SELECT * FROM university_assessments WHERE student_id = ? ORDER BY visit_number");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$university_assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
$page_title = "Student Dashboard";
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Attachment Information</h5>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Status:</div>
                    <div class="col-md-8">
                        <?php 
                            $status_badges = [
                                'pending' => 'badge-warning',
                                'matched' => 'badge-info',
                                'ongoing' => 'badge-primary',
                                'completed' => 'badge-success'
                            ];
                            $badge_class = $status_badges[$attachment_status] ?? 'badge-secondary';
                        ?>
                        <span class="badge <?php echo $badge_class; ?> p-2"><?php echo ucfirst($attachment_status); ?></span>
                    </div>
                </div>
                
                <?php if ($organization): ?>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Organization:</div>
                    <div class="col-md-8"><?php echo $organization['name']; ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Location:</div>
                    <div class="col-md-8"><?php echo $organization['location']; ?></div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">Contact Person:</div>
                    <div class="col-md-8"><?php echo $organization['contact_person']; ?></div>
                </div>
                <?php else: ?>
                <div class="alert alert-info">
                    You haven't been matched with an organization yet. The coordinator will assign you to an organization based on your preferences.
                </div>
                <?php endif; ?>
                
                <?php if ($supervisor): ?>
                <div class="row mb-3">
                    <div class="col-md-4 font-weight-bold">University Supervisor:</div>
                    <div class="col-md-8"><?php echo $supervisor['full_name']; ?> (<?php echo $supervisor['department']; ?>)</div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Progress Tracker</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Logbooks</h5>
                                <h1 class="display-4 text-center"><?php echo count($logbooks); ?></h1>
                                <p class="text-center mb-0">Submitted</p>
                                <div class="mt-3">
                                    <a href="logbook.php" class="btn btn-outline-primary btn-sm btn-block">View/Add Logbooks</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Final Report</h5>
                                <?php if ($final_report): ?>
                                <h1 class="display-4 text-center text-success"><i class="fa fa-check-circle"></i></h1>
                                <p class="text-center mb-0">Submitted on <?php echo date('M d, Y', strtotime($final_report['submission_date'])); ?></p>
                                <?php else: ?>
                                <h1 class="display-4 text-center text-warning"><i class="fa fa-exclamation-circle"></i></h1>
                                <p class="text-center mb-0">Not Submitted</p>
                                <?php endif; ?>
                                <div class="mt-3">
                                    <a href="final_report.php" class="btn btn-outline-primary btn-sm btn-block">View/Submit Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <h5 class="mt-4">Assessments</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Assessment Type</th>
                                <th>Date</th>
                                <th>Score</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($university_assessments as $assessment): ?>
                            <tr>
                                <td>University Visit #<?php echo $assessment['visit_number']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($assessment['visit_date'])); ?></td>
                                <td><?php echo $assessment['total_score']; ?>/100</td>
                                <td><span class="badge badge-success">Completed</span></td>
                            </tr>
                            <?php endforeach; ?>
                            
                            <?php if(empty($university_assessments)): ?>
                            <tr>
                                <td>University Visit #1</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <tr>
                                <td>University Visit #2</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <?php elseif(count($university_assessments) == 1): ?>
                            <tr>
                                <td>University Visit #2</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <?php endif; ?>
                            
                            <?php if($industrial_assessment): ?>
                            <tr>
                                <td>Industrial Supervisor</td>
                                <td><?php echo date('M d, Y', strtotime($industrial_assessment['submission_date'])); ?></td>
                                <td><?php echo $industrial_assessment['total_score']; ?>/100</td>
                                <td><span class="badge badge-success">Completed</span></td>
                            </tr>
                            <?php else: ?>
                            <tr>
                                <td>Industrial Supervisor</td>
                                <td>-</td>
                                <td>-</td>
                                <td><span class="badge badge-warning">Pending</span></td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Quick Links</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="logbook.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-book mr-2"></i> Submit Weekly Logbook
                    </a>
                    <a href="final_report.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-file-alt mr-2"></i> Submit Final Report
                    </a>
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-user mr-2"></i> Update Profile
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
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
