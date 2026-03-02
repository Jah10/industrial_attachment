<?php
require_once 'includes/functions.php';
include 'includes/header.php';
?>

<div class="jumbotron">
    <h1 class="display-4">Welcome to Industrial Attachment System</h1>
    <p class="lead">A platform for managing student industrial attachments efficiently</p>
    <hr class="my-4">
    <p>This system helps connect students with organizations for industrial attachment opportunities, streamline documentation and assessment processes.</p>
    
    <?php if (!isLoggedIn()) : ?>
        <div class="mt-4">
            <a class="btn btn-primary btn-lg mr-2" href="login.php" role="button">Login</a>
            <a class="btn btn-outline-primary btn-lg" href="register.php" role="button">Register</a>
        </div>
    <?php else : ?>
        <div class="mt-4">
            <?php if (isUserType('student')) : ?>
                <a class="btn btn-primary btn-lg" href="pages/student/dashboard.php" role="button">Go to Dashboard</a>
            <?php elseif (isUserType('organization')) : ?>
                <a class="btn btn-primary btn-lg" href="pages/organization/dashboard.php" role="button">Go to Dashboard</a>
            <?php elseif (isUserType('supervisor')) : ?>
                <a class="btn btn-primary btn-lg" href="pages/supervisor/dashboard.php" role="button">Go to Dashboard</a>
            <?php elseif (isUserType('coordinator')) : ?>
                <a class="btn btn-primary btn-lg" href="pages/coordinator/dashboard.php" role="button">Go to Dashboard</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">For Students</h5>
                <p class="card-text">Register for industrial attachment, submit logbooks, and track your progress.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">For Organizations</h5>
                <p class="card-text">Register your organization to host students and monitor their performance.</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">For Supervisors</h5>
                <p class="card-text">Assess students' performance and submit evaluation reports.</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>