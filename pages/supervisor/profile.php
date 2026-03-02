<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a supervisor
requireUserType('supervisor');

// Get supervisor details
$supervisor = getSupervisorDetails($conn, $_SESSION['user_id']);

// Initialize variables
$success = $error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $full_name = cleanInput($_POST['full_name']);
    $department = cleanInput($_POST['department']);
    $phone = cleanInput($_POST['phone']);
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate inputs
    if (empty($full_name) || empty($department) || empty($phone)) {
        $error = "Name, department, and phone are required fields";
    } else {
        // Get user details to check password if they want to change it
        $user = getUserDetails($conn, $_SESSION['user_id']);
        $password_change = false;
        
        // Check if user wants to change password
        if (!empty($current_password) && !empty($new_password)) {
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $error = "Current password is incorrect";
            } elseif ($new_password != $confirm_password) {
                $error = "New passwords do not match";
            } elseif (strlen($new_password) < 6) {
                $error = "New password must be at least 6 characters";
            } else {
                $password_change = true;
            }
        }
        
        if (empty($error)) {
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Update supervisor info
                $stmt = $conn->prepare("UPDATE supervisors SET full_name = ?, department = ?, phone = ? WHERE supervisor_id = ?");
                $stmt->bind_param("sssi", $full_name, $department, $phone, $supervisor['supervisor_id']);
                $stmt->execute();
                
                // Update password if requested
                if ($password_change) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                    $stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
                    $stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                
                $success = "Profile updated successfully";
                
                // Refresh supervisor details
                $supervisor = getSupervisorDetails($conn, $_SESSION['user_id']);
                
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = "Error updating profile: " . $e->getMessage();
            }
        }
    }
}

// Include header
$page_title = "Update Profile";
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Update Your Profile</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $supervisor['full_name']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="department">Department</label>
                        <input type="text" class="form-control" id="department" name="department" value="<?php echo $supervisor['department']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $supervisor['phone']; ?>" required>
                    </div>
                    
                    <hr>
                    <h5>Change Password (Optional)</h5>
                    
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password">
                        <small class="form-text text-muted">Leave blank if you don't want to change your password</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                    </div>
                    
                    <div class="text-right">
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>