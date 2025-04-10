<?php
    include 'connect.php';
    $dbname="project1";
    mysqli_select_db($conn,$dbname);
    
    $sql="ALTER TABLE user ADD COLUMN profile_image VARCHAR(255),ADD COLUMN phone VARCHAR(255),ADD COLUMN address VARCHAR(255)";
    $result=mysqli_query($conn,$sql);
    if($result)
    {
        echo "Table updated successfully";
    }
    else
    {
        echo "Table update failed";
    }

    $sql2= "ALTER TABLE user 
        ADD COLUMN status TINYINT(1) DEFAULT 1 COMMENT '0=inactive, 1=active, 2=pending'";

if ($conn->query($sql2) === TRUE) {
    echo "Column 'status' added successfully";
} else {
    echo "Error adding column: " . $conn->error;
}

?>