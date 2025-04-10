<?php
$servername = "localhost";
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = "project1"; // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SQL to alter table and add role column
$sql = "ALTER TABLE user ADD COLUMN role INT(11) NOT NULL";

if ($conn->query($sql) === TRUE) {
    echo "Column 'role' added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}

$conn->close();
?>
