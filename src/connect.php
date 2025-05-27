<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "assignet";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Помилка з'єднання: " . $conn->connect_error);
}

// $conn->set_charset("utf8mb4");
?>