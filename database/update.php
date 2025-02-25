<?php
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$database = "project"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 1: Drop foreign key constraint
$sql1 = "ALTER TABLE categories DROP FOREIGN KEY categories_ibfk_1"; // Change the constraint name if needed

if ($conn->query($sql1) === TRUE) {
    echo "Foreign key constraint removed successfully.<br>";
} else {
    echo "Error removing foreign key: " . $conn->error . "<br>";
}

// Step 2: Drop the parent_id column
$sql2 = "ALTER TABLE categories DROP COLUMN parent_id";

if ($conn->query($sql2) === TRUE) {
    echo "Column 'parent_id' removed successfully.";
} else {
    echo "Error removing column: " . $conn->error;
}

$conn->close();
?>
