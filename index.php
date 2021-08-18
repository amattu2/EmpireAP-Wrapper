<?php
// Include required class file
require("classes/EmpireAP.class.php");

// Initialize wrapper
$wrapper = new amattu\EmpireAP((isset($_GET['email']) ? $_GET['email'] : "test@example.com"),
  isset($_GET['password']) ? $_GET['password'] : "testPW123");

// Example: force login
//echo $wrapper->login() ? "Login success" : "Login failed";

// Example: fetch makes by year
//echo "<br>", "<pre>";
//print_r($wrapper->search_makers(2015));
//echo "</pre>";

// Example: fetch models by year and maker
//echo "<br>", "<pre>";
//print_r($wrapper->search_models(2015, "TOYOTA"));
//echo "</pre>";

// Example: fetch parts, wheels by year, maker, and model
//echo "<br>", "<pre>";
//print_r($wrapper->search_results(2015, "TOYOTA", 2505));
//echo "</pre>";

// Example: recent vehicle searches
//echo "<br>", "<pre>";
//print_r($wrapper->search_history());
//echo "</pre>";
?>
