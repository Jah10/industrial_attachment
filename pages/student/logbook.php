<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Ensure user is logged in as a student
requireUserType('student');

// Get student details
$student = getStudentDetails($conn, $_SESSION['user_id']);

// Process form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Form validation
    $week_number = cleanInput($_POST['week_number']);
    $start_date = cleanInput($_POST['start_date']);
    $end_date = cleanInput($_POST['end_date']);
    $activities = cleanInput($_POST['activities']);
    $challenges = cleanInput($_POST['challenges']);
    $solutions = cleanInput($_POST['solutions']);
    
    // Validate inputs
    if (empty($week_number) || empty($start_date) || empty($end_date) || empty($activities)) {
        $error = "Week number, dates, and activities are required";
    } else {
        // Check if logbook entry for this week already exists
        $stmt = $conn->prepare("SELECT logbook_id FROM logbooks WHERE student_id = ? AND week_number = ?");
        $stmt->bind_param("ii", $student['student_id'], $week_number);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing entry
            $stmt = $conn->prepare("UPDATE logbooks SET start_date = ?, end_date = ?, activities = ?, challenges = ?, solutions = ?, submission_date = CURRENT_TIMESTAMP WHERE logbook_id = ?");
            $stmt->bind_param("sssssi", $start_date, $end_date, $activities, $challenges, $solutions, $existing['logbook_id']);
            
            if ($stmt->execute()) {
                $success = "Logbook entry for Week {$week_number} updated successfully";
            } else {
                $error = "Error updating logbook entry: " . $conn->error;
            }
        } else {
            // Insert new entry
            $stmt = $conn->prepare("INSERT INTO logbooks (student_id, week_number, start_date, end_date, activities, challenges, solutions) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iisssss", $student['student_id'], $week_number, $start_date, $end_date, $activities, $challenges, $solutions);
            
            if ($stmt->execute()) {
                $success = "Logbook entry for Week {$week_number} submitted successfully";
            } else {
                $error = "Error submitting logbook entry: " . $conn->error;
            }
        }
    }
}

// Get existing logbook entries
$stmt = $conn->prepare("SELECT * FROM logbooks WHERE student_id = ? ORDER BY week_number DESC");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$logbooks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
$page_title = "Weekly Logbook";
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Weekly Logbook Entries</h5>
                <button type="button" class="btn btn-light btn-sm" data-toggle="modal" data-target="#addLogbookModal">
                    <i class="fa fa-plus"></i> Add New Entry
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (empty($logbooks)): ?>
                    <div class="alert alert-info">
                        You haven't submitted any logbook entries yet. Click "Add New Entry" to create your first weekly logbook.
                    </div>
                <?php else: ?>
                    <div class="accordion" id="logbookAccordion">
                        <?php foreach ($logbooks as $index => $logbook): ?>
                            <div class="card mb-2">
                                <div class="card-header" id="heading<?php echo $logbook['logbook_id']; ?>">
                                    <h2 class="mb-0">
                                        <button class="btn btn-link btn-block text-left d-flex justify-content-between" type="button" data-toggle="collapse" data-target="#collapse<?php echo $logbook['logbook_id']; ?>" aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $logbook['logbook_id']; ?>">
                                            <span>Week <?php echo $logbook['week_number']; ?> (<?php echo date('M d', strtotime($logbook['start_date'])); ?> - <?php echo date('M d, Y', strtotime($logbook['end_date'])); ?>)</span>
                                            <span class="text-muted small">Submitted: <?php echo date('M d, Y', strtotime($logbook['submission_date'])); ?></span>
                                        </button>
                                    </h2>
                                </div>

                                <div id="collapse<?php echo $logbook['logbook_id']; ?>" class="collapse <?php echo ($index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $logbook['logbook_id']; ?>" data-parent="#logbookAccordion">
                                    <div class="card-body">
                                        <h6 class="font-weight-bold">Activities</h6>
                                        <p><?php echo nl2br($logbook['activities']); ?></p>
                                        
                                        <h6 class="font-weight-bold">Challenges</h6>
                                        <p><?php echo !empty($logbook['challenges']) ? nl2br($logbook['challenges']) : 'None reported'; ?></p>
                                        
                                        <h6 class="font-weight-bold">Solutions/Lessons Learned</h6>
                                        <p><?php echo !empty($logbook['solutions']) ? nl2br($logbook['solutions']) : 'None reported'; ?></p>
                                        
                                        <div class="text-right mt-3">
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                onclick="editLogbook(
                                                    <?php echo $logbook['logbook_id']; ?>, 
                                                    <?php echo $logbook['week_number']; ?>, 
                                                    '<?php echo $logbook['start_date']; ?>', 
                                                    '<?php echo $logbook['end_date']; ?>', 
                                                    '<?php echo addslashes($logbook['activities']); ?>', 
                                                    '<?php echo addslashes($logbook['challenges']); ?>', 
                                                    '<?php echo addslashes($logbook['solutions']); ?>'
                                                )">
                                                <i class="fa fa-edit"></i> Edit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Logbook Modal -->
<div class="modal fade" id="addLogbookModal" tabindex="-1" role="dialog" aria-labelledby="logbookModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="logbookModalLabel">Add Weekly Logbook Entry</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label for="week_number">Week Number</label>
                            <input type="number" class="form-control" id="week_number" name="week_number" min="1" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="start_date">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                        <div class="form-group col-md-4">
                            <label for="end_date">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="activities">Activities Performed</label>
                        <textarea class="form-control" id="activities" name="activities" rows="5" required></textarea>
                        <small class="form-text text-muted">Describe the tasks and activities you performed during this week.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="challenges">Challenges Faced</label>
                        <textarea class="form-control" id="challenges" name="challenges" rows="3"></textarea>
                        <small class="form-text text-muted">Describe any challenges or difficulties you encountered.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="solutions">Solutions/Lessons Learned</label>
                        <textarea class="form-control" id="solutions" name="solutions" rows="3"></textarea>
                        <small class="form-text text-muted">Describe how you addressed challenges and what you learned.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editLogbook(id, week, start, end, activities, challenges, solutions) {
    // Set modal title
    document.getElementById('logbookModalLabel').textContent = 'Edit Weekly Logbook Entry';
    
    // Set form values
    document.getElementById('week_number').value = week;
    document.getElementById('start_date').value = start;
    document.getElementById('end_date').value = end;
    document.getElementById('activities').value = activities.replace(/\\'/g, "'");
    document.getElementById('challenges').value = challenges.replace(/\\'/g, "'");
    document.getElementById('solutions').value = solutions.replace(/\\'/g, "'");
    
    // Show modal
    $('#addLogbookModal').modal('show');
}

// Reset form when closing modal
$('#addLogbookModal').on('hidden.bs.modal', function () {
    document.getElementById('logbookModalLabel').textContent = 'Add Weekly Logbook Entry';
    document.getElementById('week_number').value = '';
    document.getElementById('start_date').value = '';
    document.getElementById('end_date').value = '';
    document.getElementById('activities').value = '';
    document.getElementById('challenges').value = '';
    document.getElementById('solutions').value = '';
});

// Set default dates when opening modal for new entry
$('#addLogbookModal').on('show.bs.modal', function () {
    if (document.getElementById('week_number').value === '') {
        // Calculate default dates (if no existing values are set)
        const today = new Date();
        const endDate = new Date(today);
        endDate.setDate(today.getDate() - (today.getDay() === 0 ? 0 : today.getDay() + 1));
        
        const startDate = new Date(endDate);
        startDate.setDate(endDate.getDate() - 6);
        
        // Format dates to YYYY-MM-DD for input fields
        document.getElementById('end_date').value = endDate.toISOString().split('T')[0];
        document.getElementById('start_date').value = startDate.toISOString().split('T')[0];
        
        // Calculate week number based on existing entries
        <?php if (!empty($logbooks)): ?>
        const lastWeek = <?php echo $logbooks[0]['week_number']; ?>;
        document.getElementById('week_number').value = lastWeek + 1;
        <?php else: ?>
        document.getElementById('week_number').value = 1;
        <?php endif; ?>
    }
});
</script>

<?php include '../../includes/footer.php'; ?>