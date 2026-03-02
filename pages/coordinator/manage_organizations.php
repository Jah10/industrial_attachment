<?php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is coordinator
requireUserType('coordinator');

// Process organization approval if applicable
if (isset($_POST['approve_org'])) {
    $org_id = (int)$_POST['org_id'];
    $approved = (int)$_POST['approved'];
    
    $update_query = "UPDATE organizations SET is_approved = ? WHERE organization_id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("ii", $approved, $org_id);
    
    if ($stmt->execute()) {
        $success_message = "Organization status updated successfully!";
    } else {
        $error_message = "Error updating organization status: " . $conn->error;
    }
}

// Get all organizations
$organizations_query = "SELECT o.*, 
                        (SELECT COUNT(*) FROM students WHERE organization_id = o.organization_id) as assigned_students 
                        FROM organizations o 
                        ORDER BY o.name ASC";
$organizations_result = $conn->query($organizations_query);

include '../../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2>Manage Organizations</h2>
            <hr>
            
            <?php if (isset($success_message)): ?>
                <?php echo showSuccess($success_message); ?>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <?php echo showError($error_message); ?>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Organizations</h5>
                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#addOrganizationModal">
                        Add Organization
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Location</th>
                                    <th>Contact Person</th>
                                    <th>Contact Email</th>
                                    <th>Students</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($organizations_result->num_rows > 0): ?>
                                    <?php while ($org = $organizations_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $org['name']; ?></td>
                                            <td><?php echo $org['location']; ?></td>
                                            <td><?php echo $org['contact_person']; ?></td>
                                            <td><?php echo $org['contact_email']; ?></td>
                                            <td><?php echo $org['assigned_students']; ?></td>
                                            <td>
                                                <?php if (isset($org['is_approved']) && $org['is_approved'] == 1): ?>
                                                    <span class="badge badge-success">Approved</span>
                                                <?php else: ?>
                                                    <span class="badge badge-warning">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info" 
                                                    data-toggle="modal" 
                                                    data-target="#viewOrgModal<?php echo $org['organization_id']; ?>">
                                                    View
                                                </button>
                                                <?php if (!isset($org['is_approved']) || $org['is_approved'] == 0): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="org_id" value="<?php echo $org['organization_id']; ?>">
                                                        <input type="hidden" name="approved" value="1">
                                                        <button type="submit" name="approve_org" class="btn btn-sm btn-success">Approve</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="org_id" value="<?php echo $org['organization_id']; ?>">
                                                        <input type="hidden" name="approved" value="0">
                                                        <button type="submit" name="approve_org" class="btn btn-sm btn-warning">Suspend</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- View Organization Modal -->
                                        <div class="modal fade" id="viewOrgModal<?php echo $org['organization_id']; ?>" tabindex="-1" role="dialog" aria-hidden="true">
                                            <div class="modal-dialog modal-lg" role="document">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?php echo $org['name']; ?> Details</h5>
                                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                            <span aria-hidden="true">&times;</span>
                                                        </button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <h6>Organization Information</h6>
                                                                <p><strong>Name:</strong> <?php echo $org['name']; ?></p>
                                                                <p><strong>Location:</strong> <?php echo $org['location']; ?></p>
                                                                <p><strong>Contact Person:</strong> <?php echo $org['contact_person']; ?></p>
                                                                <p><strong>Contact Email:</strong> <?php echo $org['contact_email']; ?></p>
                                                                <p><strong>Contact Phone:</strong> <?php echo $org['contact_phone']; ?></p>
                                                                <p><strong>Description:</strong> <?php echo $org['description']; ?></p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <h6>Requirements</h6>
                                                                <?php
                                                                $req_query = "SELECT * FROM organization_requirements WHERE organization_id = " . $org['organization_id'];
                                                                $req_result = $conn->query($req_query);
                                                                if ($req_result && $req_result->num_rows > 0) {
                                                                    while ($req = $req_result->fetch_assoc()) {
                                                                        echo "<div class='card mb-2'>";
                                                                        echo "<div class='card-body'>";
                                                                        echo "<p><strong>Skills Required:</strong> " . $req['skills_required'] . "</p>";
                                                                        echo "<p><strong>Positions:</strong> " . $req['positions_available'] . "</p>";
                                                                        echo "<p><strong>Project Description:</strong> " . $req['project_description'] . "</p>";
                                                                        echo "</div>";
                                                                        echo "</div>";
                                                                    }
                                                                } else {
                                                                    echo "<p>No requirements specified yet.</p>";
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                        
                                                        <hr>
                                                        
                                                        <h6>Assigned Students</h6>
                                                        <?php
                                                        $students_query = "SELECT * FROM students WHERE organization_id = " . $org['organization_id'];
                                                        $students_result = $conn->query($students_query);
                                                        if ($students_result && $students_result->num_rows > 0) {
                                                            echo "<table class='table table-sm'>";
                                                            echo "<thead><tr><th>Name</th><th>Reg Number</th><th>Status</th></tr></thead>";
                                                            echo "<tbody>";
                                                            while ($student = $students_result->fetch_assoc()) {
                                                                echo "<tr>";
                                                                echo "<td>" . $student['full_name'] . "</td>";
                                                                echo "<td>" . $student['registration_number'] . "</td>";
                                                                echo "<td>" . ucfirst($student['attachment_status']) . "</td>";
                                                                echo "</tr>";
                                                            }
                                                            echo "</tbody>";
                                                            echo "</table>";
                                                        } else {
                                                            echo "<p>No students assigned yet.</p>";
                                                        }
                                                        ?>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No organizations found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Organization Modal -->
<div class="modal fade" id="addOrganizationModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Organization</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="process_organization.php" method="post">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name">Organization Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="location">Location</label>
                                <input type="text" class="form-control" id="location" name="location" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_person">Contact Person</label>
                                <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="contact_email">Contact Email</label>
                                <input type="email" class="form-control" id="contact_email" name="contact_email" required>
                            </div>
                            <div class="form-group">
                                <label for="contact_phone">Contact Phone</label>
                                <input type="text" class="form-control" id="contact_phone" name="contact_phone" required>
                            </div>
                            <div class="form-group">
                                <label for="password">Initial Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <small class="form-text text-muted">The organization can change this after login.</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Organization Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                    </div>
                    
                    <hr>
                    <h6>Organization Requirements</h6>
                    
                    <div class="form-group">
                        <label for="skills_required">Skills Required</label>
                        <textarea class="form-control" id="skills_required" name="skills_required" rows="2" required></textarea>
                        <small class="form-text text-muted">Comma-separated list of skills</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="positions_available">Positions Available</label>
                                <input type="number" class="form-control" id="positions_available" name="positions_available" min="1" value="1" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="project_description">Project Description</label>
                        <textarea class="form-control" id="project_description" name="project_description" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="submit" name="add_organization" class="btn btn-primary">Add Organization</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
include '../../includes/footer.php';
?>