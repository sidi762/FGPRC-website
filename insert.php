<?php
$con=mysqli_connect("206.81.5.202","IFPRC","Ifprc_passwd123","IFVADB");
if (mysqli_connect_errno())
{
    echo "连接失败: " . mysqli_connect_errno();
}else{
echo "连接成功";
}
mysqli_select_db($con,"IFVADB");

$sql="INSERT INTO info
VALUES
('$_POST[ID]','$_POST[nameCH]','$_POST[nameEN]','$_POST[ICAO]','$_POST[IATA]','$_POST[base]','$_POST[head]','$_POST[headID]', '$_POST[group]','$_POST[groupWechat]')";

if (!mysqli_query($con, $sql))
  {
  die('Error: ' . mysqli_errno($con));
  }
echo "1 record added";

mysqli_close($con);
?>
