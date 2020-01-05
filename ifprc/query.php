<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>IFPRC虚拟航空与组织登记信息查询页</title>
 
  </head>
  <body>
    <table width="100%">
      <tbody>
        <tr>
          <td align="center"> <font size="6" color="red">IFPRC虚拟航空与组织登记信息查询页</font></td>
        </tr>
        <tr>
<!--
          <td align="center"> 
<input type="text" id="yyz1" name= "yyz1"/> 
            <input value="确认查询" onclick="calculate()" type="button"/> 
          </td>
-->
        </tr>
      </tbody>
    </table>


<?php
$con=mysqli_connect("localhost","IFPRC","Ifprc_passwd123","IFVADB");
// 检测连接
if (mysqli_connect_errno())
{
    echo "连接失败: " . mysqli_connect_error();
}

$result = mysqli_query($con,"SELECT * FROM info");

while($row = mysqli_fetch_array($result))
{
echo "<br>";
echo $row['ID'] . " " . $row['nameCH']. " ". $row['nameEN']. " ". $row['ICAO']. " ". $row['IATA']. " ". $row['base']. " ". $row['head']. " ". $row['headID']. " ". $row['group']. " ". $row['groupWechat'];
}
?>

    
    <div style="text-align:center; width:100%; hight:1dp;">
      <font size="0.1" color="blanck">IFPRC虚拟航空与组织登记信息查询页 Copyright© IFPRC-InfiniteFlightChina 2016-2019 All Rights Reserved<br><br></font> 
  
    </div>
    <style>

.box{width:360px; text-align:center; font-szie:1px;}

.box img {width:10%;}

</style>
</body>
</html>

