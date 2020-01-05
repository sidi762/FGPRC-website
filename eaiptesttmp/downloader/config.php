<?php
// Variables: 

// Database
$db_host = 'localhost'; // Hostname of database
$db_username = 'FGPRC'; // Username
$db_password = 'Fgprc2019_passwd_fly_your_Dream';  // Password
$db_name = 'eaipdownloader'; // Database name

// Set the maximum number of downloads (actually, the number of page loads)
$maxdownloads = "2";

// Set the keys' viable duration in seconds (86400 seconds = 24 hours)
$lifetime = "300";

// Set the real names of actual download files on the server as a comma-separated list (this is optional; you can use a single filename or just leave it as empty double-quotes: "")
$realfilenames = "";

// Set the name of local download file - this is what the visitor's file will actually be called when he/she saves it
$fakefilename = "eaip_fgprc.zip";

// Connect:

// Connect to the MySQL database using: hostname, username, password

$link=mysqli_connect("localhost","FGPRC","Fgprc2019_passwd_fly_your_Dream");
mysqli_select_db("eaipdownloader");
?>
