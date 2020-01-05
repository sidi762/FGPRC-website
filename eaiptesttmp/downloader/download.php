<?php
error_reporting(0);
require ('config.php');

	// GET the unique key
	if(get_magic_quotes_gpc()) {
        $id = stripslashes($_GET['id']);
	}else{
		$id = $_GET['id'];
	}
	
	// Reduce it to 12 characters, because a legit key is exactly 12 characters
	$id = substr(trim($id), 0, 12);

	// Check for tables
	$con=mysqli_connect("localhost","FGPRC","Fgprc2019_passwd_fly_your_Dream","eaipdownloader"); 
	if (mysqli_connect_errno($con)) 
	{ 
	    echo "Could not connect to database: " . mysqli_connect_error(); 
	} 
	$query = "SHOW TABLES FROM $db_name";
	$result = mysqli_query($con, $query);
	
	if (!$result) {
		echo 'The database is not correctly configured.  No tables were found in the database.';
		exit;
	}
	
	// Check for the downloadkeys table
	$query = "SELECT * FROM downloadkeys LIMIT 1";
	$result = mysqli_query($con, $query);
	if (!$result) {
		echo 'The database is not correctly configured.  Check the name of the table.';
		exit;
	}
					
	// Query the database for a match to the key
	$query = sprintf("SELECT * FROM downloadkeys WHERE uniqueid = '%s'",
	mysqli_real_escape_string($link,$id));
	$result = mysqli_query($con, $query) or die(mysqli_error());
	
	// Write the result to an array
	$row = mysqli_fetch_array($result);
	
	// Begin checking validity of the key
	if (mysqli_num_rows($result) == 0) {
		// If no match is found, return an error message and exit
		echo 'The download key you are using is invalid.';
		exit;
	}else{
		// Calculate the age of the key
		$age = date('U') - $row['timestamp'];
		$lifetime = $row['lifetime'];
		// Compare the age of the key against the allowed age
		if ($age >= $lifetime) {
			// The key is too old, so exit
			echo 'This key has expired (exceeded time allotted).';
			exit;
		}else{
			// The valid key has not expired, so check the number of downloads
			$downloads = $row['downloads'];
			$maxdownloads = $row['maxdownloads'];
			if ($downloads >= $maxdownloads) {
				// The number of downloads meets (or exceeds) the allowed number of downloads, so exit
				echo 'This key has expired (exceeded allowed downloads).';
				exit;
			}else{
				// The key has passed all validation checks
				// Retrieve the filename of the download
				$realfilename = $row['filename'];
				// Increment the download counter
				$downloads += 1;
				$sql = sprintf("UPDATE downloadkeys SET downloads = '" . $downloads . "' WHERE uniqueid = '%s'",
	mysqli_real_escape_string($link, $id));
				$incrementdownloads = mysqli_query($con, $sql) or die(mysqli_error());
				mysqli_close($con);
				
// Debug		echo "Key validated.";

// Force the browser to start the download automatically
// Consider http://www.php.net/manual/en/function.header.php#86554 for problems with large downloads

				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . basename($fakefilename) . '"');
				header('Content-Transfer-Encoding: binary');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($realfilename));
				ob_clean();
				flush();
				readfile($realfilename);
				exit;
			}
		}
	}
?>
