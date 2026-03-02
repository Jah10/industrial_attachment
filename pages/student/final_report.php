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
    $title = cleanInput($_POST['title']);
    $content = cleanInput($_POST['content']);
    
    // Validate inputs
    if (empty($title) || empty($content)) {
        $error = "Report title and content are required";
    } else {
        // Check if a report already exists
        $stmt = $conn->prepare("SELECT report_id FROM final_reports WHERE student_id = ?");
        $stmt->bind_param("i", $student['student_id']);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            // Update existing report
            $stmt = $conn->prepare("UPDATE final_reports SET title = ?, content = ?, submission_date = CURRENT_TIMESTAMP WHERE report_id = ?");
            $stmt->bind_param("ssi", $title, $content, $existing['report_id']);
            
            if ($stmt->execute()) {
                $success = "Final report updated successfully";
            } else {
                $error = "Error updating report: " . $conn->error;
            }
        } else {
            // Insert new report
            $stmt = $conn->prepare("INSERT INTO final_reports (student_id, title, content) VALUES (?, ?, ?)");
            $stmt->bind_param("iss", $student['student_id'], $title, $content);
            
            if ($stmt->execute()) {
                $success = "Final report submitted successfully";
            } else {
                $error = "Error submitting report: " . $conn->error;
            }
        }
    }
}

// Get existing report if any
$stmt = $conn->prepare("SELECT * FROM final_reports WHERE student_id = ?");
$stmt->bind_param("i", $student['student_id']);
$stmt->execute();
$report = $stmt->get_result()->fetch_assoc();

// Include header
$page_title = "Final Report";
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-lg-12">
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Final Attachment Report</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="mb-4">
                    <p>The final report is an important part of your industrial attachment assessment. Please provide a comprehensive summary of your experience, skills gained, challenges faced, and lessons learned.</p>
                    
                    <?php if ($report): ?>
                        <div class="alert alert-info">
                            <strong>Report Status:</strong> Submitted on <?php echo date('M d, Y', strtotime($report['submission_date'])); ?>
                            <button class="btn btn-sm btn-info float-right" id="editReportBtn">Edit Report</button>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($report && !isset($_GET['edit'])): ?>
                    <div id="reportDisplay">
                        <h4><?php echo $report['title']; ?></h4>
                        <hr>
                        <div class="report-content">
                            <?php echo nl2br($report['content']); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <form method="POST" action="" id="reportForm">
                        <div class="form-group">
                            <label for="title">Report Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo $report['title'] ?? ''; ?>" required>
                            <small class="form-text text-muted">A descriptive title for your attachment report.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Report Content</label>
                            <textarea class="form-control" id="content" name="content" rows="20" required><?php echo $report['content'] ?? ''; ?></textarea>
                            <small class="form-text text-muted">Include sections such as introduction, organization overview, tasks performed, skills gained, challenges faced, solutions implemented, and conclusion.</small>
                        </div>
                        
                        <div class="text-right">
                            <?php if ($report): ?>
                                <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                            <?php endif; ?>
                            <button type="submit" class="btn btn-primary">Submit Report</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    // Toggle between view and edit mode
    document.addEventListener('DOMContentLoaded', function() {
        if (document.getElementById('editReportBtn')) {
            document.getElementById('editReportBtn').addEventListener('click', function() {
                document.getElementById('reportDisplay').style.display = 'none';
                document.getElementById('reportForm').style.display = 'block';
            });
        }
        
        if (document.getElementById('cancelEditBtn')) {
            document.getElementById('cancelEditBtn').addEventListener('click', function() {
                document.getElementById('reportForm').style.display = 'none';
                document.getElementById('reportDisplay').style.display = 'block';
            });
        }
    });
</script>

<?php include '../../includes/footer.php'; ?>