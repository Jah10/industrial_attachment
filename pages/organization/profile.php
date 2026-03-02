<?php
// Include necessary files
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is logged in and is an organization
requireUserType('organization');

// Get organization details
$user_id = $_SESSION['user_id'];
$orgDetails = getOrganizationDetails($conn, $user_id);

// Process form submission for updating profile
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate and sanitize input
    $name = cleanInput($_POST['name']);
    $location = cleanInput($_POST['location']);
    $contact_person = cleanInput($_POST['contact_person']);
    $contact_email = cleanInput($_POST['contact_email']);
    $contact_phone = cleanInput($_POST['contact_phone']);
    $description = cleanInput($_POST['description']);
    
    // Validate inputs
    if (empty($name) || empty($location) || empty($contact_person) || 
        empty($contact_email) || empty($contact_phone)) {
        $errorMessage = "All fields except description are required.";
    } elseif (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
        $errorMessage = "Please enter a valid email address.";
    } else {
        // Update organization profile
        $stmt = $conn->prepare("UPDATE organizations SET name = ?, location = ?, 
                              contact_person = ?, contact_email = ?, contact_phone = ?, 
                              description = ? WHERE user_id = ?");
        $stmt->bind_param("ssssssi", $name, $location, $contact_person, 
                         $contact_email, $contact_phone, $description, $user_id);
        
        if ($stmt->execute()) {
            $successMessage = "Profile updated successfully!";
            // Refresh organization details
            $orgDetails = getOrganizationDetails($conn, $user_id);
        } else {
            $errorMessage = "Error updating profile: " . $conn->error;
        }
    }
}

// Get organization requirements
$stmt = $conn->prepare("SELECT * FROM organization_requirements WHERE organization_id = ?");
$stmt->bind_param("i", $orgDetails['organization_id']);
$stmt->execute();
$requirements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Process form submission for adding/updating requirements
if (isset($_POST['submit_requirements'])) {
    $skills_required = cleanInput($_POST['skills_required']);
    $positions_available = (int)$_POST['positions_available'];
    $project_description = cleanInput($_POST['project_description']);
    $requirement_id = isset($_POST['requirement_id']) ? (int)$_POST['requirement_id'] : 0;
    
    if (empty($skills_required) || $positions_available <= 0) {
        $errorMessage = "Skills and positions are required.";
    } else {
        if ($requirement_id > 0) {
            // Update existing requirement
            $stmt = $conn->prepare("UPDATE organization_requirements SET skills_required = ?, 
                                  positions_available = ?, project_description = ? 
                                  WHERE requirement_id = ? AND organization_id = ?");
            $stmt->bind_param("sisii", $skills_required, $positions_available, 
                             $project_description, $requirement_id, $orgDetails['organization_id']);
        } else {
            // Add new requirement
            $stmt = $conn->prepare("INSERT INTO organization_requirements 
                                  (organization_id, skills_required, positions_available, project_description) 
                                  VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isis", $orgDetails['organization_id'], $skills_required, 
                             $positions_available, $project_description);
        }
        
        if ($stmt->execute()) {
            $successMessage = "Requirements updated successfully!";
            // Refresh requirements
            $stmt = $conn->prepare("SELECT * FROM organization_requirements WHERE organization_id = ?");
            $stmt->bind_param("i", $orgDetails['organization_id']);
            $stmt->execute();
            $requirements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } else {
            $errorMessage = "Error updating requirements: " . $conn->error;
        }
    }
}

// Delete requirement
if (isset($_GET['delete_requirement']) && is_numeric($_GET['delete_requirement'])) {
    $req_id = (int)$_GET['delete_requirement'];
    
    $stmt = $conn->prepare("DELETE FROM organization_requirements 
                          WHERE requirement_id = ? AND organization_id = ?");
    $stmt->bind_param("ii", $req_id, $orgDetails['organization_id']);
    
    if ($stmt->execute()) {
        $successMessage = "Requirement deleted successfully!";
        // Refresh requirements
        $stmt = $conn->prepare("SELECT * FROM organization_requirements WHERE organization_id = ?");
        $stmt->bind_param("i", $orgDetails['organization_id']);
        $stmt->execute();
        $requirements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $errorMessage = "Error deleting requirement: " . $conn->error;
    }
}

// Include header
include_once '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Organization Profile</h2>
        <?php if (!empty($successMessage)) echo showSuccess($successMessage); ?>
        <?php if (!empty($errorMessage)) echo showError($errorMessage); ?>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Organization Information</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Organization Name</label>
                        <input type="text" class="form-control" id="name" name="name" 
                              value="<?php echo htmlspecialchars($orgDetails['name'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" class="form-control" id="location" name="location" 
                              value="<?php echo htmlspecialchars($orgDetails['location'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_person">Contact Person</label>
                        <input type="text" class="form-control" id="contact_person" name="contact_person" 
                              value="<?php echo htmlspecialchars($orgDetails['contact_person'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_email">Contact Email</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                              value="<?php echo htmlspecialchars($orgDetails['contact_email'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="contact_phone">Contact Phone</label>
                        <input type="text" class="form-control" id="contact_phone" name="contact_phone" 
                              value="<?php echo htmlspecialchars($orgDetails['contact_phone'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                 rows="4"><?php echo htmlspecialchars($orgDetails['description'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Update Profile</button>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h4>Student Requirements</h4>
            </div>
            <div class="card-body">
                <?php if (count($requirements) > 0): ?>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Skills Required</th>
                                <th>Positions</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requirements as $req): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($req['skills_required']); ?></td>
                                    <td><?php echo htmlspecialchars($req['positions_available']); ?></td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-info" data-toggle="modal" 
                                               data-target="#editRequirementModal" 
                                               data-id="<?php echo $req['requirement_id']; ?>"
                                               data-skills="<?php echo htmlspecialchars($req['skills_required']); ?>"
                                               data-positions="<?php echo $req['positions_available']; ?>"
                                               data-description="<?php echo htmlspecialchars($req['project_description']); ?>">
                                            Edit
                                        </button>
                                        <a href="?delete_requirement=<?php echo $req['requirement_id']; ?>" 
                                          class="btn btn-sm btn-danger" 
                                          onclick="return confirm('Are you sure you want to delete this requirement?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No requirements added yet.</p>
                <?php endif; ?>
                
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#addRequirementModal">
                    Add New Requirement
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Requirement Modal -->
<div class="modal fade" id="addRequirementModal" tabindex="-1" role="dialog" aria-labelledby="addRequirementModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addRequirementModalLabel">Add New Requirement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="skills_required">Skills Required</label>
                        <input type="text" class="form-control" id="skills_required" name="skills_required" required>
                    </div>
                    <div class="form-group">
                        <label for="positions_available">Positions Available</label>
                        <input type="number" class="form-control" id="positions_available" name="positions_available" 
                              min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="project_description">Project Description</label>
                        <textarea class="form-control" id="project_description" name="project_description" 
                                 rows="4"></textarea>
                    </div>
                    <button type="submit" name="submit_requirements" class="btn btn-primary">Add Requirement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Requirement Modal -->
<div class="modal fade" id="editRequirementModal" tabindex="-1" role="dialog" aria-labelledby="editRequirementModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editRequirementModalLabel">Edit Requirement</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" action="">
                    <input type="hidden" name="requirement_id" id="edit_requirement_id">
                    <div class="form-group">
                        <label for="edit_skills_required">Skills Required</label>
                        <input type="text" class="form-control" id="edit_skills_required" name="skills_required" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_positions_available">Positions Available</label>
                        <input type="number" class="form-control" id="edit_positions_available" name="positions_available" 
                              min="1" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_project_description">Project Description</label>
                        <textarea class="form-control" id="edit_project_description" name="project_description" 
                                 rows="4"></textarea>
                    </div>
                    <button type="submit" name="submit_requirements" class="btn btn-primary">Update Requirement</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Script to populate the edit modal with existing data
    $('#editRequirementModal').on('show.bs.modal', function (event) {
        var button = $(event.relatedTarget);
        var id = button.data('id');
        var skills = button.data('skills');
        var positions = button.data('positions');
        var description = button.data('description');
        
        var modal = $(this);
        modal.find('#edit_requirement_id').val(id);
        modal.find('#edit_skills_required').val(skills);
        modal.find('#edit_positions_available').val(positions);
        modal.find('#edit_project_description').val(description);
    });
</script>

<?php include_once '../../includes/footer.php'; ?>