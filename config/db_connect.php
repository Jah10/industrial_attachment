<?php
// Database connection parameters
	$dbhost = 'localhost';
	$dbuser = 'root';
	$dbpass = '';
	$dbname = 'industrial_attachment';
	
    //create connection
    $conn = new mysqli($dbhost, $dbuser, $dbpass, $dbname);
		
	// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

?>