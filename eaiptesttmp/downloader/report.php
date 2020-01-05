<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Download Key Usage Report</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<meta name="author" content="Oliver Baty | http://www.ardamis.com/" />
<style type="text/css">
#wrapper {
	font: 15px Verdana, Arial, Helvetica, sans-serif;
	margin: 40px 100px 0 100px;
}
.box {
	border: 1px solid #e5e5e5;
	padding: 6px;
	background: #f5f5f5;
}
input {
	font-size: 1em;
}
#submit {
	padding: 4px 8px;
}
</style>
</head>

<body>
<div id="wrapper">

<h2>Download Key Usage Report</h2>

<!-- add a count of keys created and downloads to date -->

<?php 
require ('config.php');
	
$con=mysqli_connect("localhost","FGPRC","Fgprc2019_passwd_fly_your_Dream","eaipdownloader"); 
if (mysqli_connect_errno($con)) 
{ 
    echo "Could not connect to database: " . mysqli_connect_error(); 
} 
$query = "SELECT * FROM downloadkeys";
$result = mysqli_query($con, $query) or die(mysqli_error());
mysqli_close($con);

if (mysqli_num_rows($result) == 0) { 
	echo '<h2 class="warning">There are no keys in the database.</h2>';
} else {
?>

	<table id="keystable" cellpadding="2" cellspacing="2" summary="List of all keys">
	<thead>
		<tr>
			<th>No.</th>
			<th>Key</th>
			<th>Date Generated</th>
			<th>File Name</th>
			<th>No. Downloads</th>
			<th>Notes</th>
		</tr>
	</thead>
	<tbody>
	
<?php
	$num = 0;
	$downloadcounter = 0;
	while($row = mysqli_fetch_array($result)) {
		$num++;
		$downloadcounter += $row['downloads'];
		echo "<tr>\n";
		echo "<td>" . $num . "</td>";
		echo "<td>" . $row['uniqueid'] . "</td>";
		echo "<td>" . date('n/j/y g:i A', $row['timestamp']) . "</td>";
		echo "<td>" . $row['filename'] . "</td>";
		echo '<td style="text-align:center;">' . $row['downloads'] . '</td>';
		echo ($row['note'])? "<td>" . $row['note'] . "</td>\n" : "<td>&nbsp;</td>\n";
		echo "</tr>\n";
	} ?>
    
    	<tr>
        	<td><strong>Totals</strong></td>
         	<td></td>
	       	<td></td>
         	<td></td>
            <td></td>
        	<td></td>
        </tr>
    	<tr>
        	<td><?php echo $num; ?></td>
        	<td></td>
        	<td></td>
        	<td></td>
        	<td style="text-align:center;"><?php echo $downloadcounter; ?></td>
        	<td></td>
        </tr>

	</tbody>
	</table>
	
<?php } ?>

</div>
</body>
</html>
