<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a student
requireUserType('student');

// Get user details
$user = getUserDetails($conn, $_SESSION['user_id']);
$student = getStudentDetails($conn, $_SESSION['user_id']);

// Process form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form validation
    $full_name = cleanInput($_POST['full_name']);
    $email = cleanInput($_POST['email']);
    $phone = cleanInput($_POST['phone']);
    $skills = cleanInput($_POST['skills']);
    $preferred_location = cleanInput($_POST['preferred_location']);
    $preferred_project_type = cleanInput($_POST['preferred_project_type']);
    
    // Validate inputs
    if (empty($full_name) || empty($email) || empty($phone)) {
        $error = "Full name, email, and phone are required fields";
    } else {
        // Begin transaction
        $conn->begin_transaction();
        
        try {
            // Update user table
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->bind_param("si", $email, $_SESSION['user_id']);
            $stmt->execute();
            
            // Update student table
            $stmt = $conn->prepare("UPDATE students SET full_name = ?, phone = ?, skills = ?, preferred_location = ?, preferred_project_type = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $full_name, $phone, $skills, $preferred_location, $preferred_project_type, $_SESSION['user_id']);
            $stmt->execute();
            
            // If password change is requested
            if (!empty($_POST['new_password']) && !empty($_POST['confirm_password'])) {
                if ($_POST['new_password'] === $_POST['confirm_password']) {
                    $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                    $stmt->execute();
                } else {
                    throw new Exception("New password and confirmation do not match");
                }
            }
            
            // Commit transaction
            $conn->commit();
            $success = "Profile updated successfully";
            
            // Refresh student data
            $student = getStudentDetails($conn, $_SESSION['user_id']);
            $user = getUserDetails($conn, $_SESSION['user_id']);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $error = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Include header
$page_title = "Student Profile";
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">My Profile</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="row">
                        <!-- Personal Information -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Personal Information</h5>
                            <div class="form-group">
                                <label for="full_name">Full Name</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $student['full_name']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="registration_number">Registration Number</label>
                                <input type="text" class="form-control" id="registration_number" value="<?php echo $student['registration_number']; ?>" readonly>
                                <small class="form-text text-muted">Registration number cannot be changed.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email']; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $student['phone']; ?>" required>
                            </div>
                        </div>
                        
                        <!-- Attachment Preferences -->
                        <div class="col-md-6">
                            <h5 class="mb-3">Attachment Preferences</h5>
                            <div class="form-group">
                                <label for="skills">Skills</label>
                                <textarea class="form-control" id="skills" name="skills" rows="3"><?php echo $student['skills']; ?></textarea>
                                <small class="form-text text-muted">List your technical skills, separated by commas.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="preferred_location">Preferred Location</label>
                                <input type="text" class="form-control" id="preferred_location" name="preferred_location" value="<?php echo $student['preferred_location']; ?>">
                                <small class="form-text text-muted">Enter your preferred city or region for attachment.</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="preferred_project_type">Preferred Project Type</label>
                                <textarea class="form-control" id="preferred_project_type" name="preferred_project_type" rows="3"><?php echo $student['preferred_project_type']; ?></textarea>
                                <small class="form-text text-muted">Describe the type of projects you would like to work on.</small>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <!-- Password Change Section -->
                    <h5 class="mb-3">Change Password</h5>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="confirm_password">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <small class="form-text text-muted">Leave blank if you don't want to change your password.</small>
                        </div>
                    </div>
                    
                    <div class="text-right mt-4">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>