<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is coordinator
requireUserType('coordinator');

// Get summary statistics
$total_orgs_query = "SELECT COUNT(*) as total FROM organizations";
$total_orgs_result = $conn->query($total_orgs_query);
$total_orgs = $total_orgs_result->fetch_assoc()['total'];

$total_students_query = "SELECT COUNT(*) as total FROM students";
$total_students_result = $conn->query($total_students_query);
$total_students = $total_students_result->fetch_assoc()['total'];

$pending_students_query = "SELECT COUNT(*) as total FROM students WHERE attachment_status = 'pending'";
$pending_students_result = $conn->query($pending_students_query);
$pending_students = $pending_students_result->fetch_assoc()['total'];

$matched_students_query = "SELECT COUNT(*) as total FROM students WHERE attachment_status = 'matched'";
$matched_students_result = $conn->query($matched_students_query);
$matched_students = $matched_students_result->fetch_assoc()['total'];

// Get recent logs
$recent_logs_query = "SELECT s.full_name, l.week_number, l.submission_date 
                     FROM logbooks l 
                     JOIN students s ON l.student_id = s.student_id 
                     ORDER BY l.submission_date DESC LIMIT 5";
$recent_logs_result = $conn->query($recent_logs_query);

// Get reminders
$reminders = getReminders($conn, 'coordinator');

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2>Coordinator Dashboard</h2>
            <hr>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Organizations</h5>
                    <h1 class="card-text"><?php echo $total_orgs; ?></h1>
                    <a href="manage_organizations.php" class="btn btn-light btn-sm">Manage</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Students</h5>
                    <h1 class="card-text"><?php echo $total_students; ?></h1>
                    <a href="match_students.php" class="btn btn-light btn-sm">View All</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Pending Students</h5>
                    <h1 class="card-text"><?php echo $pending_students; ?></h1>
                    <a href="match_students.php?filter=pending" class="btn btn-light btn-sm">Match</a>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Matched Students</h5>
                    <h1 class="card-text"><?php echo $matched_students; ?></h1>
                    <a href="match_students.php?filter=matched" class="btn btn-light btn-sm">View</a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5>Recent Student Logs</h5>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Week</th>
                                <th>Submission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($recent_logs_result->num_rows > 0): ?>
                                <?php while ($log = $recent_logs_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $log['full_name']; ?></td>
                                        <td>Week <?php echo $log['week_number']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($log['submission_date'])); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center">No recent logs</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5>Reminders</h5>
                </div>
                <div class="card-body">
                    <?php if (count($reminders) > 0): ?>
                        <ul class="list-group">
                            <?php foreach ($reminders as $reminder): ?>
                                <li class="list-group-item">
                                    <h6><?php echo $reminder['title']; ?></h6>
                                    <p><?php echo $reminder['description']; ?></p>
                                    <small class="text-muted">Due: <?php echo date('M d, Y', strtotime($reminder['due_date'])); ?></small>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-center">No active reminders</p>
                    <?php endif; ?>
                    
                    <hr>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addReminderModal">
                        Add Reminder
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Reminder Modal -->
<div class="modal fade" id="addReminderModal" tabindex="-1" role="dialog" aria-labelledby="addReminderModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addReminderModalLabel">Add New Reminder</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="add_reminder.php" method="post">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input type="date" class="form-control" id="due_date" name="due_date" required>
                    </div>
                    <div class="form-group">
                        <label for="user_type">User Type</label>
                        <select class="form-control" id="user_type" name="user_type" required>
                            <option value="student">Students</option>
                            <option value="supervisor">Supervisors</option>
                            <option value="organization">Organizations</option>
                            <option value="coordinator">Coordinators</option>
                            <option value="all">All Users</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Add Reminder</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include '../../includes/footer.php';
?>