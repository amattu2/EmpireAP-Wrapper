<?php
// Include required class file
require("classes/EmpireAP.class.php");

// Initialize wrapper
$wrapper = new amattu\EmpireAP("test@example.com", "testPW123");

// Example: force login 
$wrapper->login();
?>
