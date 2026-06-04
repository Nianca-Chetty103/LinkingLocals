<?php
// ─── MySQLi connection (for existing code) ───
$host = "sql107.infinityfree.com";
$user = "if0_42052645";
$pass = "Linking123Local";
$db   = "if0_42052645_linkinglocals";


$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// ─── PDO connection (for settings/new code) ───
try {
  $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
  die("Connection failed: " . $e->getMessage());
}
?>