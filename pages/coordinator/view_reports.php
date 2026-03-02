<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is coordinator
requireUserType('coordinator');

// Initialize variables
$report_type = isset($_GET['type']) ? $_GET['type'] : 'student_progress';
$filter_id = isset($_GET['filter_id']) ? (int)$_GET['filter_id'] : 0;

// Get organizations for filter
$organizations_query = "SELECT organization_id, name FROM organizations ORDER BY name ASC";
$organizations_result = $conn->query($organizations_query);

// Get supervisors for filter
$supervisors_query = "SELECT supervisor_id, full_name FROM supervisors ORDER BY full_name ASC";
$supervisors_result = $conn->query($supervisors_query);

// Initialize report data array
$report_data = [];

// Generate report based on selected type
if ($report_type === 'student_progress') {
    // Query for student progress report
    $query = "SELECT s.student_id, s.full_name, s.registration_number, s.attachment_status, 
              o.name as organization_name, 
              (SELECT COUNT(*) FROM logbooks WHERE student_id = s.student_id) as logbook_count,
              (SELECT MAX(week_number) FROM logbooks WHERE student_id = s.student_id) as latest_week
              FROM students s
              LEFT JOIN organizations o ON s.organization_id = o.organization_id
              WHERE s.attachment_status != 'pending'";
    
    if ($filter_id > 0) {
        $query .= " AND s.organization_id = $filter_id";
    }
    
    $query .= " ORDER BY s.full_name ASC";
    $result = $conn->query($query);
    
    if ($result) {
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
    }
} elseif ($report_type === 'organization_stats') {
    // Query for organization statistics
    $query = "SELECT o.organization_id, o.name, o.location, 
              COUNT(s.student_id) as total_students,
              SUM(CASE WHEN s.attachment_status = 'ongoing' THEN 1 ELSE 0 END) as active_students,
              SUM(CASE WHEN s.attachment_status = 'completed' THEN 1 ELSE 0 END) as completed_students
              FROM organizations o
              LEFT JOIN students s ON o.organization_id = s.organization_id
              GROUP BY o.organization_id";
    
    if ($filter_id > 0) {
        $query .= " HAVING o.organization_id = $filter_id";
    }
    
    $query .= " ORDER BY o.name ASC";
    $result = $conn->query($query);
    
    if ($result) {
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
    }
} elseif ($report_type === 'assessment_summary') {
    // Query for assessment summary
    $query = "SELECT s.student_id, s.full_name, s.registration_number, o.name as organization_name,
              ia.total_score as industrial_score,
              (SELECT AVG(ua.total_score) FROM university_assessments ua WHERE ua.student_id = s.student_id) as university_score,
              (SELECT COUNT(*) FROM final_reports fr WHERE fr.student_id = s.student_id) as has_final_report
              FROM students s
              LEFT JOIN organizations o ON s.organization_id = o.organization_id
              LEFT JOIN industrial_assessments ia ON s.student_id = ia.student_id
              WHERE s.attachment_status IN ('ongoing', 'completed')";
    
    if ($filter_id > 0) {
        $query .= " AND (s.organization_id = $filter_id OR 
                        EXISTS (SELECT 1 FROM student_supervisor ss WHERE ss.student_id = s.student_id AND ss.supervisor_id = $filter_id))";
    }
    
    $query .= " GROUP BY s.student_id ORDER BY s.full_name ASC";
    $result = $conn->query($query);
    
    if ($result) {
        $report_data = $result->fetch_all(MYSQLI_ASSOC);
    }
}

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2>Reports & Analytics</h2>
            <hr>
            
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'student_progress' ? 'active' : ''; ?>" 
                               href="view_reports.php?type=student_progress">
                                Student Progress
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'organization_stats' ? 'active' : ''; ?>" 
                               href="view_reports.php?type=organization_stats">
                                Organization Statistics
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $report_type === 'assessment_summary' ? 'active' : ''; ?>" 
                               href="view_reports.php?type=assessment_summary">
                                Assessment Summary
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <!-- Filter options -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form class="form-inline">
                                <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                                
                                <?php if ($report_type === 'student_progress' || $report_type === 'organization_stats'): ?>
                                    <div class="form-group mr-2">
                                        <label for="filter_id" class="mr-2">Organization:</label>
                                        <select class="form-control" id="filter_id" name="filter_id">
                                            <option value="0">All Organizations</option>
                                            <?php 
                                            if ($organizations_result) {
                                                while ($org = $organizations_result->fetch_assoc()) {
                                                    $selected = ($filter_id == $org['organization_id']) ? 'selected' : '';
                                                    echo "<option value='" . $org['organization_id'] . "' $selected>" . $org['name'] . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                <?php elseif ($report_type === 'assessment_summary'): ?>
                                    <div class="form-group mr-2">
                                        <label for="filter_id" class="mr-2">Filter by:</label>
                                        <select class="form-control" id="filter_id" name="filter_id">
                                            <option value="0">All</option>
                                            <optgroup label="Organizations">
                                                <?php 
                                                if ($organizations_result) {
                                                    $organizations_result->data_seek(0);
                                                    while ($org = $organizations_result->fetch_assoc()) {
                                                        $selected = ($filter_id == $org['organization_id']) ? 'selected' : '';
                                                        echo "<option value='" . $org['organization_id'] . "' $selected>" . $org['name'] . "</option>";
                                                    }
                                                }
                                                ?>
                                            </optgroup>
                                            <optgroup label="Supervisors">
                                                <?php 
                                                if ($supervisors_result) {
                                                    while ($sup = $supervisors_result->fetch_assoc()) {
                                                        $selected = ($filter_id == $sup['supervisor_id']) ? 'selected' : '';
                                                        echo "<option value='" . $sup['supervisor_id'] . "' $selected>" . $sup['full_name'] . "</option>";
                                                    }
                                                }
                                                ?>
                                            </optgroup>
                                        </select>
                                    </div>
                                <?php endif; ?>
                                
                                <button type="submit" class="btn btn-primary">Apply Filter</button>
                            </form>
                        </div>
                        <div class="col-md-6 text-right">
                            <button onclick="window.print()" class="btn btn-success">
                                <i class="fa fa-print"></i> Print Report
                            </button>
                        </div>
                    </div>
                    
                    <!-- Report content -->
                    <?php if ($report_type === 'student_progress'): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Registration No.</th>
                                        <th>Organization</th>
                                        <th>Status</th>
                                        <th>Logbooks Submitted</th>
                                        <th>Current Week</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($report_data) > 0): ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td><?php echo $row['full_name']; ?></td>
                                                <td><?php echo $row['registration_number']; ?></td>
                                                <td><?php echo $row['organization_name']; ?></td>
                                                <td>
                                                    <?php
                                                    $status_class = '';
                                                    switch ($row['attachment_status']) {
                                                        case 'matched': $status_class = 'badge-info'; break;
                                                        case 'ongoing': $status_class = 'badge-primary'; break;
                                                        case 'completed': $status_class = 'badge-success'; break;
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst($row['attachment_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $row['logbook_count']; ?></td>
                                                <td><?php echo $row['latest_week'] ? $row['latest_week'] : 'N/A'; ?></td>
                                                <td>
                                                    <?php
                                                        $progress = 0;
                                                        if ($row['logbook_count'] > 0) {
                                                            // Assuming 8 weeks of attachment
                                                            $progress = min(100, ($row['latest_week'] / 8) * 100);
                                                        }
                                                    ?>
                                                    <div class="progress">
                                                        <div class="progress-bar" role="progressbar" style="width: <?php echo $progress; ?>%" 
                                                            aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100">
                                                            <?php echo round($progress); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No data available for this report</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($report_type === 'organization_stats'): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Organization</th>
                                        <th>Location</th>
                                        <th>Total Students</th>
                                        <th>Active Students</th>
                                        <th>Completed Attachments</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($report_data) > 0): ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td><?php echo $row['name']; ?></td>
                                                <td><?php echo $row['location']; ?></td>
                                                <td><?php echo $row['total_students']; ?></td>
                                                <td><?php echo $row['active_students']; ?></td>
                                                <td><?php echo $row['completed_students']; ?></td>
                                                <td>
                                                    <?php if ($row['active_students'] > 0): ?>
                                                        <span class="badge badge-primary">Active</span>
                                                    <?php elseif ($row['total_students'] > 0): ?>
                                                        <span class="badge badge-success">Completed</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">No Students</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="6" class="text-center">No data available for this report</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php elseif ($report_type === 'assessment_summary'): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Registration No.</th>
                                        <th>Organization</th>
                                        <th>Industrial Score</th>
                                        <th>University Score</th>
                                        <th>Final Report</th>
                                        <th>Overall</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($report_data) > 0): ?>
                                        <?php foreach ($report_data as $row): ?>
                                            <tr>
                                                <td><?php echo $row['full_name']; ?></td>
                                                <td><?php echo $row['registration_number']; ?></td>
                                                <td><?php echo $row['organization_name']; ?></td>
                                                <td>
                                                    <?php if (isset($row['industrial_score'])): ?>
                                                        <?php echo $row['industrial_score']; ?> / 100
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($row['university_score'])): ?>
                                                        <?php echo round($row['university_score'], 1); ?> / 100
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($row['has_final_report'] > 0): ?>
                                                        <span class="badge badge-success">Submitted</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-warning">Pending</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                        $overall = null;
                                                        $industrial_weight = 0.4;
                                                        $university_weight = 0.4;
                                                        $report_weight = 0.2;
                                                        
                                                        if (isset($row['industrial_score']) && isset($row['university_score']) && $row['has_final_report'] > 0) {
                                                            // Assuming final report is assessed elsewhere and here we just check if it exists
                                                            $report_score = 100; // Placeholder for report score
                                                            $overall = ($row['industrial_score'] * $industrial_weight) + 
                                                                      ($row['university_score'] * $university_weight) + 
                                                                      ($report_score * $report_weight);
                                                            echo round($overall, 1) . " / 100";
                                                        } else {
                                                            echo "<span class='badge badge-warning'>Incomplete</span>";
                                                        }
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No data available for this report</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../../includes/footer.php';
?>