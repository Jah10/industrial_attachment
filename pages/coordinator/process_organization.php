<?php
// process_organization.php
require_once '../../config/db_connect.php';
require_once '../../includes/functions.php';

// Check if user is coordinator
requireUserType('coordinator');

if (isset($_POST['add_organization'])) {
    // Get form data
    $name = cleanInput($_POST['name']);
    $location = cleanInput($_POST['location']);
    $contact_person = cleanInput($_POST['contact_person']);
    $contact_email = cleanInput($_POST['contact_email']);
    $contact_phone = cleanInput($_POST['contact_phone']);
    $description = cleanInput($_POST['description']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    
    // Skills and requirements
    $skills_required = cleanInput($_POST['skills_required']);
    $positions_available = (int)$_POST['positions_available'];
    $project_description = cleanInput($_POST['project_description']);
    
    // Start a transaction
    $conn->begin_transaction();
    
    try {
        // Create user account for organization
        $user_query = "INSERT INTO users (username, password, email, user_type) VALUES (?, ?, ?, 'organization')";
        $user_stmt = $conn->prepare($user_query);
        $username = strtolower(str_replace(' ', '_', $name)); // Create username from org name
        $user_stmt->bind_param("sss", $username, $password, $contact_email);
        $user_stmt->execute();
        
        $user_id = $conn->insert_id;
        
        // Add organization details
        $org_query = "INSERT INTO organizations (user_id, name, location, contact_person, contact_email, contact_phone, description, is_approved) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
        $org_stmt = $conn->prepare($org_query);
        $approved = 1; // Auto-approve when added by coordinator
        $org_stmt->bind_param("isssssi", $user_id, $name, $location, $contact_person, $contact_email, $contact_phone, $description);
        $org_stmt->execute();
        
        $org_id = $conn->insert_id;
        
        // Add organization requirements
        $req_query = "INSERT INTO organization_requirements (organization_id, skills_required, positions_available, project_description) 
                     VALUES (?, ?, ?, ?)";
        $req_stmt = $conn->prepare($req_query);
        $req_stmt->bind_param("isis", $org_id, $skills_required, $positions_available, $project_description);
        $req_stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect back with success message
        header("Location: manage_organizations.php?success=Organization+added+successfully");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        header("Location: manage_organizations.php?error=Error+adding+organization:+" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Invalid request
    header("Location: manage_organizations.php");
    exit();
}
?>