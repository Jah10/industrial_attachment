<?php
// Include necessary files
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an organization
requireUserType('organization');

// Get organization details
$user_id = $_SESSION['user_id'];
$orgDetails = getOrganizationDetails($conn, $user_id);

// Get all students assigned to this organization
$stmt = $conn->prepare("SELECT s.student_id, s.full_name, s.registration_number, s.attachment_status 
                      FROM students s 
                      WHERE s.organization_id = ?");
$stmt->bind_param("i", $orgDetails['organization_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get organization requirements
$stmt = $conn->prepare("SELECT * FROM organization_requirements WHERE organization_id = ?");
$stmt->bind_param("i", $orgDetails['organization_id']);
$stmt->execute();
$requirements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get pending assessments (students who don't have assessments yet)
$stmt = $conn->prepare("SELECT s.student_id, s.full_name, s.registration_number 
                       FROM students s 
                       LEFT JOIN industrial_assessments a ON s.student_id = a.student_id AND a.organization_id = ?
                       WHERE s.organization_id = ? AND s.attachment_status = 'ongoing' AND a.assessment_id IS NULL");
$stmt->bind_param("ii", $orgDetails['organization_id'], $orgDetails['organization_id']);
$stmt->execute();
$pendingAssessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get reminders
$reminders = getReminders($conn, 'organization');

// Include header
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Organization Dashboard</h2>
        <p>Welcome, <?php echo htmlspecialchars($orgDetails['name']); ?>!</p>
    </div>
</div>

<!-- Reminders Section -->
<?php if (count($reminders) > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card bg-light">
            <div class="card-header">
                <h4>Reminders</h4>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($reminders as $reminder): ?>
                        <li class="list-group-item">
                            <strong><?php echo htmlspecialchars($reminder['title']); ?></strong>
                            <span class="badge badge-primary float-right">Due: <?php echo date('M d, Y', strtotime($reminder['due_date'])); ?></span>
                            <p class="mb-0"><?php echo htmlspecialchars($reminder['description']); ?></p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row">
    <!-- Assigned Students Section -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h4>Assigned Students</h4>
            </div>
            <div class="card-body">
                <?php if (count($students) > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Registration Number</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($student['registration_number']); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo ($student['attachment_status'] == 'ongoing') ? 'success' : 'secondary'; ?>">
                                            <?php echo ucfirst($student['attachment_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No students are currently assigned to your organization.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions Section -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h4>Quick Actions</h4>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="profile.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-user"></i> Update Organization Profile
                    </a>
                    <a href="submit_assessment.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-clipboard"></i> Submit Student Assessment
                    </a>
                </div>
            </div>
        </div>

        <!-- Pending Assessments Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h4>Pending Assessments</h4>
            </div>
            <div class="card-body">
                <?php if (count($pendingAssessments) > 0): ?>
                    <div class="alert alert-warning">
                        You have <?php echo count($pendingAssessments); ?> student(s) requiring assessment.
                    </div>
                    <ul class="list-group">
                        <?php foreach ($pendingAssessments as $student): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <?php echo htmlspecialchars($student['full_name']); ?>
                                <a href="submit_assessment.php?student_id=<?php echo $student['student_id']; ?>" class="btn btn-sm btn-primary">Assess</a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p>No pending assessments required.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Organization Requirements Section -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4>Current Requirements</h4>
            </div>
            <div class="card-body">
                <?php if (count($requirements) > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Skills Required</th>
                                <th>Positions Available</th>
                                <th>Project Description</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['skills_required']); ?></td>
                                    <td><?php echo htmlspecialchars($req['positions_available']); ?></td>
                                    <td><?php echo htmlspecialchars($req['project_description']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No requirements added yet. <a href="profile.php">Add requirements</a> to help match with suitable students.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<?php include_once '../../includes/footer.php'; ?>