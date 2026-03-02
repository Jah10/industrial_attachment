<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is coordinator
requireUserType('coordinator');

$success_message = '';
$error_message = '';

// Process match student if form is submitted
if (isset($_POST['match_student'])) {
    $student_id = (int)$_POST['student_id'];
    $organization_id = (int)$_POST['organization_id'];
    
    // Update student's organization and status
    $update_query = "UPDATE students SET organization_id = ?, attachment_status = 'matched' WHERE student_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $organization_id, $student_id);
    
    if ($stmt->execute()) {
        $success_message = "Student successfully matched with organization!";
    } else {
        $error_message = "Error matching student: " . $conn->error;
    }
}

// Process assigning supervisor if form is submitted
if (isset($_POST['assign_supervisor'])) {
    $student_id = (int)$_POST['student_id'];
    $supervisor_id = (int)$_POST['supervisor_id'];
    
    // Check if assignment already exists
    $check_query = "SELECT * FROM student_supervisor WHERE student_id = ? AND supervisor_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $student_id, $supervisor_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        $error_message = "This student is already assigned to this supervisor.";
    } else {
        // Insert new assignment
        $insert_query = "INSERT INTO student_supervisor (student_id, supervisor_id) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("ii", $student_id, $supervisor_id);
        
        if ($insert_stmt->execute()) {
            $success_message = "Supervisor successfully assigned to student!";
        } else {
            $error_message = "Error assigning supervisor: " . $conn->error;
        }
    }
}

// Get filter parameters
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';

// Base query for students
$students_query = "SELECT s.*, o.name as organization_name 
                  FROM students s 
                  LEFT JOIN organizations o ON s.organization_id = o.organization_id";

// Apply filters
if ($filter === 'pending') {
    $students_query .= " WHERE s.attachment_status = 'pending'";
} elseif ($filter === 'matched') {
    $students_query .= " WHERE s.attachment_status = 'matched'";
} elseif ($filter === 'ongoing') {
    $students_query .= " WHERE s.attachment_status = 'ongoing'";
} elseif ($filter === 'completed') {
    $students_query .= " WHERE s.attachment_status = 'completed'";
}

$students_query .= " ORDER BY s.full_name ASC";
$students_result = $conn->query($students_query);

// Get all organizations
$organizations_query = "SELECT o.*, 
                      (SELECT COUNT(*) FROM students WHERE organization_id = o.organization_id) as current_students,
                      (SELECT SUM(positions_available) FROM organization_requirements WHERE organization_id = o.organization_id) as positions
                      FROM organizations o";
$organizations_result = $conn->query($organizations_query);

// Get all supervisors
$supervisors_query = "SELECT * FROM supervisors ORDER BY full_name ASC";
$supervisors_result = $conn->query($supervisors_query);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2>Match Students with Organizations</h2>
            <hr>
            
            <?php if (!empty($success_message)): ?>
                <?php echo showSuccess($success_message); ?>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <?php echo showError($error_message); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'all' ? 'active' : ''; ?>" href="match_students.php">All Students</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'pending' ? 'active' : ''; ?>" href="match_students.php?filter=pending">Pending</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'matched' ? 'active' : ''; ?>" href="match_students.php?filter=matched">Matched</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'ongoing' ? 'active' : ''; ?>" href="match_students.php?filter=ongoing">Ongoing</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $filter === 'completed' ? 'active' : ''; ?>" href="match_students.php?filter=completed">Completed</a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Registration No.</th>
                                    <th>Skills</th>
                                    <th>Preferred Location</th>
                                    <th>Project Type</th>
                                    <th>Status</th>
                                    <th>Organization</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($students_result && $students_result->num_rows > 0): ?>
                                    <?php while ($student = $students_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $student['full_name']; ?></td>
                                            <td><?php echo $student['registration_number']; ?></td>
                                            <td><?php echo $student['skills']; ?></td>
                                            <td><?php echo $student['preferred_location']; ?></td>
                                            <td><?php echo $student['preferred_project_type']; ?></td>
                                            <td>
                                                <?php
                                                    $status_class = '';
                                                    switch ($student['attachment_status']) {
                                                        case 'pending': $status_class = 'badge-warning'; break;
                                                        case 'matched': $status_class = 'badge-info'; break;
                                                        case 'ongoing': $status_class = 'badge-primary'; break;
                                                        case 'completed': $status_class = 'badge-success'; break;
                                                    }
                                                ?>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($student['attachment_status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo isset($student['organization_name']) ? $student['organization_name'] : 'Not Assigned'; ?></td>
                                            <td>
                                                <?php if ($student['attachment_status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-primary"
                                                            data-toggle="modal"
                                                            data-target="#matchStudentModal<?php echo $student['student_id']; ?>">
                                                        Match
                                                    </button>
                                                <?php else: ?>
                                                    <!-- Supervisor assignment button -->
                                                    <button type="button" class="btn btn-sm btn-info"
                                                            data-toggle="modal"
                                                            data-target="#assignSupervisorModal<?php echo $student['student_id']; ?>">
                                                        Supervisor
                                                    </button>
                                                    
                                                    <?php if ($student['attachment_status'] === 'matched'): ?>
                                                        <!-- Button to change status to ongoing -->
                                                        <form method="post" class="d-inline">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                            <input type="hidden" name="new_status" value="ongoing">
                                                            <button type="submit" name="update_status" class="btn btn-sm btn-success">
                                                                Start
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Match Student Modal -->
                                        <div class="modal fade" id="matchStudentModal<?php echo $student['student_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Match Student to Organization</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h6>Student Information</h6>
                                                        <p><strong>Name:</strong> <?php echo $student['full_name']; ?></p>
                                                        <p><strong>Registration Number:</strong> <?php echo $student['registration_number']; ?></p>
                                                        <p><strong>Skills:</strong> <?php echo $student['skills']; ?></p>
                                                        <p><strong>Preferred Location:</strong> <?php echo $student['preferred_location']; ?></p>
                                                        <p><strong>Preferred Project Type:</strong> <?php echo $student['preferred_project_type']; ?></p>
                                                        
                                                        <hr>
                                                        
                                                        <form method="post">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                            
                                                            <div class="form-group">
                                                                <label for="organization_id">Select Organization</label>
                                                                <select class="form-control" id="organization_id" name="organization_id" required>
                                                                    <option value="">-- Select Organization --</option>
                                                                    <?php
                                                                    if ($organizations_result) {
                                                                        // Reset result pointer
                                                                        $organizations_result->data_seek(0);
                                                                        
                                                                        while ($org = $organizations_result->fetch_assoc()) {
                                                                            $positions_available = (int)$org['positions'] - (int)$org['current_students'];
                                                                            if ($positions_available > 0) {
                                                                                echo "<option value='" . $org['organization_id'] . "'>" . $org['name'] . " (" . $positions_available . " positions available)</option>";
                                                                            }
                                                                        }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <button type="submit" name="match_student" class="btn btn-primary">Match Student</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Assign Supervisor Modal -->
                                        <div class="modal fade" id="assignSupervisorModal<?php echo $student['student_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Assign Supervisor to Student</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <h6>Student Information</h6>
                                                        <p><strong>Name:</strong> <?php echo $student['full_name']; ?></p>
                                                        <p><strong>Registration Number:</strong> <?php echo $student['registration_number']; ?></p>
                                                        <p><strong>Organization:</strong> <?php echo isset($student['organization_name']) ? $student['organization_name'] : 'Not Assigned'; ?></p>
                                                        
                                                        <hr>
                                                        
                                                        <!-- Current supervisors assigned to this student -->
                                                        <?php
                                                        $current_supervisors_query = "SELECT s.* FROM supervisors s 
                                                                                    JOIN student_supervisor ss ON s.supervisor_id = ss.supervisor_id 
                                                                                    WHERE ss.student_id = " . $student['student_id'];
                                                        $current_supervisors_result = $conn->query($current_supervisors_query);
                                                        ?>
                                                        
                                                        <?php if ($current_supervisors_result && $current_supervisors_result->num_rows > 0): ?>
                                                            <h6>Currently Assigned Supervisors</h6>
                                                            <ul class="list-group mb-3">
                                                                <?php while ($supervisor = $current_supervisors_result->fetch_assoc()): ?>
                                                                    <li class="list-group-item">
                                                                        <?php echo $supervisor['full_name']; ?> (<?php echo $supervisor['department']; ?>)
                                                                    </li>
                                                                <?php endwhile; ?>
                                                            </ul>
                                                        <?php endif; ?>
                                                        
                                                        <form method="post">
                                                            <input type="hidden" name="student_id" value="<?php echo $student['student_id']; ?>">
                                                            
                                                            <div class="form-group">
                                                                <label for="supervisor_id">Select Supervisor</label>
                                                                <select class="form-control" id="supervisor_id" name="supervisor_id" required>
                                                                    <option value="">-- Select Supervisor --</option>
                                                                    <?php
                                                                    if ($supervisors_result) {
                                                                        // Reset result pointer
                                                                        $supervisors_result->data_seek(0);
                                                                        
                                                                        while ($supervisor = $supervisors_result->fetch_assoc()) {
                                                                            echo "<option value='" . $supervisor['supervisor_id'] . "'>" . $supervisor['full_name'] . " (" . $supervisor['department'] . ")</option>";
                                                                        }
                                                                    }
                                                                    ?>
                                                                </select>
                                                            </div>
                                                            
                                                            <button type="submit" name="assign_supervisor" class="btn btn-primary">Assign Supervisor</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No students found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Processing -->
<?php
if (isset($_POST['update_status'])) {
    $student_id = (int)$_POST['student_id'];
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE students SET attachment_status = ? WHERE student_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $student_id);
    
    if ($stmt->execute()) {
        echo '<script>window.location.reload();</script>';
    } else {
        echo showError("Error updating status: " . $conn->error);
    }
}
?>

<?php
include '../../includes/footer.php';
?>