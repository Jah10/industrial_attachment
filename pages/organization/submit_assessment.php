<?php
// Include necessary files
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an organization
requireUserType('organization');

// Get organization details
$user_id = $_SESSION['user_id'];
$orgDetails = getOrganizationDetails($conn, $user_id);

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $student_id = (int)$_POST['student_id'];
    $punctuality = (int)$_POST['punctuality'];
    $teamwork = (int)$_POST['teamwork'];
    $problem_solving = (int)$_POST['problem_solving'];
    $technical_skills = (int)$_POST['technical_skills'];
    $communication = (int)$_POST['communication'];
    $overall_remarks = cleanInput($_POST['overall_remarks']);
    
    // Calculate total score
    $total_score = $punctuality + $teamwork + $problem_solving + $technical_skills + $communication;
    
    // Validate inputs
    if (empty($student_id) || empty($overall_remarks)) {
        $errorMessage = "All fields are required.";
    } elseif ($punctuality < 1 || $punctuality > 5 || $teamwork < 1 || $teamwork > 5 || 
             $problem_solving < 1 || $problem_solving > 5 || $technical_skills < 1 || 
             $technical_skills > 5 || $communication < 1 || $communication > 5) {
        $errorMessage = "All ratings must be between 1 and 5.";
    } else {
        // Check if assessment already exists
        $stmt = $conn->prepare("SELECT assessment_id FROM industrial_assessments 
                              WHERE student_id = ? AND organization_id = ?");
        $stmt->bind_param("ii", $student_id, $orgDetails['organization_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing assessment
            $assessment = $result->fetch_assoc();
            $stmt = $conn->prepare("UPDATE industrial_assessments SET 
                                  punctuality = ?, teamwork = ?, problem_solving = ?, 
                                  technical_skills = ?, communication = ?, overall_remarks = ?, 
                                  total_score = ?, submission_date = CURRENT_TIMESTAMP 
                                  WHERE assessment_id = ?");
            $stmt->bind_param("iiiiisii", $punctuality, $teamwork, $problem_solving, 
                            $technical_skills, $communication, $overall_remarks, 
                            $total_score, $assessment['assessment_id']);
        } else {
            // Insert new assessment
            $stmt = $conn->prepare("INSERT INTO industrial_assessments 
                                  (student_id, organization_id, punctuality, teamwork, 
                                  problem_solving, technical_skills, communication, 
                                  overall_remarks, total_score) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiiiiisi", $student_id, $orgDetails['organization_id'], 
                            $punctuality, $teamwork, $problem_solving, $technical_skills, 
                            $communication, $overall_remarks, $total_score);
        }
        
        if ($stmt->execute()) {
            $successMessage = "Assessment submitted successfully!";
        } else {
            $errorMessage = "Error submitting assessment: " . $conn->error;
        }
    }
}

// Get all students assigned to this organization
$stmt = $conn->prepare("SELECT s.student_id, s.full_name, s.registration_number 
                      FROM students s 
                      WHERE s.organization_id = ? AND s.attachment_status = 'ongoing'");
$stmt->bind_param("i", $orgDetails['organization_id']);
$stmt->execute();
$students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all assessments done by this organization
$stmt = $conn->prepare("SELECT a.*, s.full_name, s.registration_number 
                      FROM industrial_assessments a 
                      JOIN students s ON a.student_id = s.student_id 
                      WHERE a.organization_id = ? 
                      ORDER BY a.submission_date DESC");
$stmt->bind_param("i", $orgDetails['organization_id']);
$stmt->execute();
$assessments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Include header
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Submit Student Assessment</h2>
        <?php if (!empty($successMessage)) echo showSuccess($successMessage); ?>
        <?php if (!empty($errorMessage)) echo showError($errorMessage); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Assessment Form</h4>
            </div>
            <div class="card-body">
                <?php if (count($students) > 0): ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="student_id">Select Student</label>
                            <select class="form-control" id="student_id" name="student_id" required>
                                <option value="">-- Select Student --</option>
                                <?php foreach ($students as $student): ?>
                                    <option value="<?php echo $student['student_id']; ?>">
                                        <?php echo htmlspecialchars($student['full_name']) . 
                                               ' (' . htmlspecialchars($student['registration_number']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Punctuality (1-5)</label>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="punctuality" 
                                              id="punctuality<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="form-check-label" for="punctuality<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Teamwork (1-5)</label>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="teamwork" 
                                              id="teamwork<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="form-check-label" for="teamwork<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Problem Solving (1-5)</label>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="problem_solving" 
                                              id="problem_solving<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="form-check-label" for="problem_solving<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Technical Skills (1-5)</label>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="technical_skills" 
                                              id="technical_skills<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="form-check-label" for="technical_skills<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Communication (1-5)</label>
                            <div class="rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="communication" 
                                              id="communication<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                        <label class="form-check-label" for="communication<?php echo $i; ?>">
                                            <?php echo $i; ?>
                                        </label>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="overall_remarks">Overall Remarks</label>
                            <textarea class="form-control" id="overall_remarks" name="overall_remarks" 
                                     rows="4" required></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Assessment</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        No students are currently assigned to your organization or they are not in the 'ongoing' status.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Previous Assessments</h4>
            </div>
            <div class="card-body">
                <?php if (count($assessments) > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Student</th>
                                <th>Total Score</th>
                                <th>Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($assessments as $assessment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($assessment['full_name']); ?></td>
                                    <td><?php echo $assessment['total_score']; ?>/25</td>
                                    <td><?php echo date('M d, Y', strtotime($assessment['submission_date'])); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" 
                                               data-target="#viewAssessmentModal" 
                                               data-id="<?php echo $assessment['assessment_id']; ?>"
                                               data-student="<?php echo htmlspecialchars($assessment['full_name']); ?>"
                                               data-punctuality="<?php echo $assessment['punctuality']; ?>"
                                               data-teamwork="<?php echo $assessment['teamwork']; ?>"
                                               data-problem="<?php echo $assessment['problem_solving']; ?>"
                                               data-technical="<?php echo $assessment['technical_skills']; ?>"
                                               data-communication="<?php echo $assessment['communication']; ?>"
                                               data-remarks="<?php echo htmlspecialchars($assessment['overall_remarks']); ?>">
                                            View
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No assessments submitted yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- View Assessment Modal -->
<div class="modal fade" id="viewAssessmentModal" tabindex="-1" role="dialog" aria-labelledby="viewAssessmentModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewAssessmentModalLabel">Assessment Details</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <h5 id="view_student_name"></h5>
                <table class="table">
                    <tr>
                        <th>Punctuality:</th>
                        <td id="view_punctuality"></td>
                    </tr>
                    <tr>
                        <th>Teamwork:</th>
                        <td id="view_teamwork"></td>
                    </tr>
                    <tr>
                        <th>Problem Solving:</th>
                        <td id="view_problem_solving"></td>
                    </tr>
                    <tr>
                        <th>Technical Skills:</th>
                        <td id="view_technical_skills"></td>
                    </tr>
                    <tr>
                        <th>Communication:</th>
                        <td id="view_communication"></td>
                    </tr>
                    <tr>
                        <th>Overall Remarks:</th>
                        <td id="view_remarks"></td>
                    </tr>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Script to populate the assessment view modal with data
    $('#viewAssessmentModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var student = button.data('student');
        var punctuality = button.data('punctuality');
        var teamwork = button.data('teamwork');
        var problem = button.data('problem');
        var technical = button.data('technical');
        var communication = button.data('communication');
        var remarks = button.data('remarks');
        
        var modal = $(this);
        modal.find('#view_student_name').text(student);
        modal.find('#view_punctuality').text(punctuality);
        modal.find('#view_teamwork').text(teamwork);
        modal.find('#view_problem_solving').text(problem);
        modal.find('#view_technical_skills').text(technical);
        modal.find('#view_communication').text(communication);
        modal.find('#view_remarks').text(remarks);
    });
</script>

<?php include_once '../../includes/footer.php'; ?>