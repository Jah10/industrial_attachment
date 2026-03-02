<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();
session_destroy();

$baseUrl = "/~mot00531/industrial_attachment_system";
header("Location: $baseUrl/login.php?logout=success");
exit();
?>
