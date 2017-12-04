<?php
$servername = dbhost;
$username = dbuser;
$password = dbpassword;
$dbname = db;
//die($servername."<br>".$username."<br>".$password."<br>".$dbname);

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

?>