<?php
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get common user data
    $username = cleanInput($_POST['username']);
    $email = cleanInput($_POST['email']);
    $user_type = cleanInput($_POST['user_type']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long";
    } else {
        // Check if username or email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username or email already exists";
        } else {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Insert user
                $stmt = $conn->prepare("INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $hashed_password, $email, $user_type);
                $stmt->execute();
                
                $user_id = $conn->insert_id;
                
                // Insert specific user type details
                if ($user_type == 'student') {
                    $full_name = cleanInput($_POST['full_name']);
                    $registration_number = cleanInput($_POST['registration_number']);
                    $phone = cleanInput($_POST['phone']);
                    $skills = cleanInput($_POST['skills']);
                    $preferred_location = cleanInput($_POST['preferred_location']);
                    $preferred_project_type = cleanInput($_POST['preferred_project_type']);
                    
                    $stmt = $conn->prepare("INSERT INTO students (user_id, full_name, registration_number, phone, skills, preferred_location, preferred_project_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssss", $user_id, $full_name, $registration_number, $phone, $skills, $preferred_location, $preferred_project_type);
                    $stmt->execute();
                } 
                elseif ($user_type == 'organization') {
                    $name = cleanInput($_POST['name']);
                    $location = cleanInput($_POST['location']);
                    $contact_person = cleanInput($_POST['contact_person']);
                    $contact_email = cleanInput($_POST['contact_email']);
                    $contact_phone = cleanInput($_POST['contact_phone']);
                    $description = cleanInput($_POST['description']);
                    
                    $stmt = $conn->prepare("INSERT INTO organizations (user_id, name, location, contact_person, contact_email, contact_phone, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("issssss", $user_id, $name, $location, $contact_person, $contact_email, $contact_phone, $description);
                    $stmt->execute();
                } 
                elseif ($user_type == 'supervisor') {
                    $full_name = cleanInput($_POST['full_name']);
                    $department = cleanInput($_POST['department']);
                    $phone = cleanInput($_POST['phone']);
                    
                    $stmt = $conn->prepare("INSERT INTO supervisors (user_id, full_name, department, phone) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("isss", $user_id, $full_name, $department, $phone);
                    $stmt->execute();
                }
                
                // Commit transaction
                $conn->commit();
                
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
            } catch (Exception $e) {
                // Roll back transaction on error
                $conn->rollback();
                $error = "Registration failed: " . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-form">
    <h2>Register</h2>
    
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)) : ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (empty($success)) : ?>
    <form method="POST" action="" class="needs-validation" novalidate>
        <div class="form-group">
            <label for="user_type">Register as:</label>
            <select class="form-control" id="user_type" name="user_type" required onchange="showRelevantFields()">
                <option value="">Select type</option>
                <option value="student">Student</option>
                <option value="organization">Organization</option>
                <option value="supervisor">University Supervisor</option>
            </select>
            <div class="invalid-feedback">
                Please select a user type.
            </div>
        </div>
        
        <!-- Common fields for all users -->
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback">
                Please choose a username.
            </div>
        </div>
        
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" class="form-control" id="email" name="email" required>
            <div class="invalid-feedback">
                Please provide a valid email.
            </div>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="invalid-feedback">
                Please enter a password.
            </div>
            <small class="form-text text-muted">Password must be at least 8 characters.</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            <div class="invalid-feedback">
                Please confirm your password.
            </div>
        </div>
        
        <!-- Student-specific fields -->
        <div id="student_fields" style="display: none;">
            <h4 class="mt-4">Student Information</h4>
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name">
            </div>
            
            <div class="form-group">
                <label for="registration_number">Registration Number</label>
                <input type="text" class="form-control" id="registration_number" name="registration_number">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" class="form-control" id="phone" name="phone">
            </div>
            
            <div class="form-group">
                <label for="skills">Skills</label>
                <textarea class="form-control" id="skills" name="skills" rows="3"></textarea>
                <small class="form-text text-muted">Enter skills separated by commas</small>
            </div>
            
            <div class="form-group">
                <label for="preferred_location">Preferred Location</label>
                <input type="text" class="form-control" id="preferred_location" name="preferred_location">
            </div>
            
            <div class="form-group">
                <label for="preferred_project_type">Preferred Project Type</label>
                <textarea class="form-control" id="preferred_project_type" name="preferred_project_type" rows="3"></textarea>
            </div>
        </div>
        
        <!-- Organization-specific fields -->
        <div id="organization_fields" style="display: none;">
            <h4 class="mt-4">Organization Information</h4>
            
            <div class="form-group">
                <label for="name">Organization Name</label>
                <input type="text" class="form-control" id="name" name="name">
            </div>
            
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" class="form-control" id="location" name="location">
            </div>
            
            <div class="form-group">
                <label for="contact_person">Contact Person</label>
                <input type="text" class="form-control" id="contact_person" name="contact_person">
            </div>
            
            <div class="form-group">
                <label for="contact_email">Contact Email</label>
                <input type="email" class="form-control" id="contact_email" name="contact_email">
            </div>
            
            <div class="form-group">
                <label for="contact_phone">Contact Phone</label>
                <input type="text" class="form-control" id="contact_phone" name="contact_phone">
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
            </div>
        </div>
        
        <!-- Supervisor-specific fields -->
        <div id="supervisor_fields" style="display: none;">
            <h4 class="mt-4">Supervisor Information</h4>
            
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" class="form-control" id="full_name_supervisor" name="full_name">
            </div>
            
            <div class="form-group">
                <label for="department">Department</label>
                <input type="text" class="form-control" id="department" name="department">
            </div>
            
            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="text" class="form-control" id="phone_supervisor" name="phone">
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block mt-4">Register</button>
    </form>
    
    <div class="text-center mt-3">
        <p>Already have an account? <a href="login.php">Login</a></p>
    </div>
    <?php endif; ?>
</div>

<script>
function showRelevantFields() {
    const userType = document.getElementById('user_type').value;
    
    // Hide all specific fields
    document.getElementById('student_fields').style.display = 'none';
    document.getElementById('organization_fields').style.display = 'none';
    document.getElementById('supervisor_fields').style.display = 'none';
    
    // Show fields based on selected user type
    if (userType === 'student') {
        document.getElementById('student_fields').style.display = 'block';
    } else if (userType === 'organization') {
        document.getElementById('organization_fields').style.display = 'block';
    } else if (userType === 'supervisor') {
        document.getElementById('supervisor_fields').style.display = 'block';
    }
}

// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        // Fetch all forms to apply validation styles
        const forms = document.getElementsByClassName('needs-validation');
        
        // Loop over them and prevent submission if invalid
        Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();
</script>

<?php include 'includes/footer.php'; ?>