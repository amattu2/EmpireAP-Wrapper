<?php
/*
 * Produced: Thu Jan 06 2022
 * Author: Alec M.
 * GitHub: https://amattu.com/links/github
 * Copyright: (C) 2022 Alec M.
 * License: License GNU Affero General Public License v3.0
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

// Include required class file
require(__DIR__ . "/EmpireAP.class.php");

// Pull Config
$config = parse_ini_file(__DIR__ . '/config.ini') or die("No Config File Found");

// Initialize wrapper
$wrapper = new amattu\EmpireAP($config["EMPIRE_EMAIL"], $config["EMPIRE_PASSWORD"]) or die ("Wrapper Setup Failed");

// Get example function
$example = isset($_GET['example']) ? (int) $_GET['example'] : 0;

/**
 * Example 1: Force session login
 * This is automatically called when you use a function that requires authentication
 */
if ($example === 1) {
  echo "Example 1: Force session login<br>";
  echo $wrapper->login() ? "Login success" : "Login failed";
}

/**
 * Example 2: Search for Manufacturers by Model Year
 */
if ($example === 2) {
  echo "Example 2: Search for Manufacturers by Model Year<br>";
  echo "<pre>";
  print_r($wrapper->search_makers(2015));
  echo "</pre>";
}

/**
 * Example 3: Search for Models by Manufacturer and Model Year
 */
if ($example === 3) {
  echo "Example 3: Search for Models by Manufacturer and Model Year<br>";
  echo "<pre>";
  print_r($wrapper->search_models(2015, "TOYOTA"));
  echo "</pre>";
}

/**
 * Example 4: Search for Body Parts and Wheels by Model Year, Make, & Model ID
 */
if ($example === 4) {
  echo "Example 4: Search for Body Parts and Wheels by Model Year, Make, & Model ID<br>";
  echo "<pre>";
  print_r($wrapper->search_results(2015, "TOYOTA", 2505));
  echo "</pre>";
}

/**
 * Example 5: Return all recent vehicle searches
 */
if ($example === 5) {
  echo "Example 5: Return all recent vehicle searches<br>";
  echo "<pre>";
  print_r($wrapper->search_history());
  echo "</pre>";
}

/**
 * Example 6: Get a Invoice by Invoice Number
 * This will fetch any invoice, regardless of whether or not the logged-in account owns it
 * Please use wisely.
 */
if ($example === 6 && $pdf = $wrapper->get_invoice(30846609)) {
  header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
  header("Cache-Control: no-cache");
  header("Pragma: no-cache");
  header("Content-type:application/pdf");
  header("Content-Disposition:inline;filename='Invoice.pdf'");
  echo $pdf;
}

/**
 * Example 7: Get a Invoice Item's Transaction History
 * This will fetch any transaction history for any item, regardless of whether or not the logged-in account owns it
 */
if ($example === 7) {
  echo "Example 7: Get a Invoice Item's Transaction History<br>";
  echo "<pre>";
  print_r($wrapper->item_transactions(12452558));
  echo "</pre>";
}