
<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = "/~mot00531/industrial_attachment_system";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Industrial Attachment System</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/industrial_attachment_system/assets/css/style.css">

</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?php echo $baseUrl; ?>/index.php">Industrial Attachment System</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <?php if (isset($_SESSION['user_id'])) : ?>
                        <?php if ($_SESSION['user_type'] == 'student') : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/student/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/student/logbook.php">Logbook</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/student/final_report.php">Final Report</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/student/profile.php">Profile</a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'organization') : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/organization/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/organization/submit_assessment.php">Submit Assessment</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/organization/profile.php">Profile</a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'supervisor') : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/supervisor/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/supervisor/assess_students.php">Assess Students</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/supervisor/view_logbooks.php">View Logbooks</a>
                            </li>
                        <?php elseif ($_SESSION['user_type'] == 'coordinator') : ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/coordinator/dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/coordinator/manage_organizations.php">Organizations</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/coordinator/match_students.php">Match Students</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo $baseUrl; ?>/pages/coordinator/view_reports.php">Reports</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
						<a class="nav-link" href="<?php echo $baseUrl; ?>/logout.php">Logout</a>
                        </li>
                    <?php else : ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo $baseUrl; ?>/register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">

