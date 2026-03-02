<?php
require_once 'config/db_connect.php';
require_once 'includes/functions.php';

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect based on user type
    if (isUserType('student')) {
        header("Location: pages/student/dashboard.php");
    } elseif (isUserType('organization')) {
        header("Location: pages/organization/dashboard.php");
    } elseif (isUserType('supervisor')) {
        header("Location: pages/supervisor/dashboard.php");
    } elseif (isUserType('coordinator')) {
        header("Location: pages/coordinator/dashboard.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = cleanInput($_POST['username']);
    $password = $_POST['password'];
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        // Check user in database
        $stmt = $conn->prepare("SELECT user_id, username, password, user_type FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_type'] = $user['user_type'];
                
                // Redirect based on user type
                if ($user['user_type'] == 'student') {
                    header("Location: pages/student/dashboard.php");
                } elseif ($user['user_type'] == 'organization') {
                    header("Location: pages/organization/dashboard.php");
                } elseif ($user['user_type'] == 'supervisor') {
                    header("Location: pages/supervisor/dashboard.php");
                } elseif ($user['user_type'] == 'coordinator') {
                    header("Location: pages/coordinator/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "User does not exist";
        }
    }
}

include 'includes/header.php';
?>

<div class="auth-form">
    <h2>Login</h2>
    
    <?php if (!empty($error)) : ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" class="needs-validation" novalidate>
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" class="form-control" id="username" name="username" required>
            <div class="invalid-feedback">
                Please enter your username.
            </div>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" class="form-control" id="password" name="password" required>
            <div class="invalid-feedback">
                Please enter your password.
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">Login</button>
    </form>
    
    <div class="text-center mt-3">
        <p>Don't have an account? <a href="register.php">Register</a></p>
    </div>
</div>

<?php include 'includes/footer.php'; ?>