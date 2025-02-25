<?php
    include 'connect.php';
    $dbname="project";

    $sql="CREATE DATABASE IF NOT EXISTS $dbname";
    if(mysqli_query($conn,$sql))
    {
        // echo "database created";
    }
    else
    {
        // die("error".mysqli_error($conn));
    }
    mysqli_close($conn);
?>