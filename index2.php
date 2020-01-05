<!DOCTYPE html>
<html>
  <head>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
    <title>IFPRC虚航备案系统测试页</title>
 
  </head>
  <body>
    <table width="100%">
      <tbody>
        <tr>
          <td align="center"> <font size="6" color="red">IFPRC虚航备案系统测试页</font></td>
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
<p>添加虚航记录</p>
<form action="insert.php" method="post">
ID: <input type="text" name="ID" />
中文名称: <input type="text" name="nameCH" />
英文名称:<input type="text" name="nameEN" />
ICAO代码:<input type="text" name="ICAO" />
IATA代码:<input type="text" name="IATA" />
基地机场ICAO代码:<input type="text" name="base" />
负责人:<input type="text" name="head" />
负责人IFPRC呼号:<input type="text" name="headID" />
QQ群号:<input type="text" name="group" />
微信群号（选填）:<input type="text" name="groupWechat" />

<input type="submit" />
</form>

<?php
$con=mysqli_connect("206.81.5.202","IFPRC","Ifprc_passwd123","IFVADB");
// 检测连接
if (mysqli_connect_errno())
{
    echo "连接失败: " . mysqli_connect_error();
}

$result = mysqli_query($con,"SELECT * FROM info");
echo "<br>";
echo "--------------------------------------------------------------";
echo "<br>";
echo "所有虚航查询";
while($row = mysqli_fetch_array($result))
{
echo "<br>";
echo $row['ID'] . " " . $row['nameCH']. " ". $row['nameEN'];
}
?>

    
    <div style="text-align:center; width:100%; hight:1dp;">
      <font size="0.1" color="blanck">测试页面<br><br></font> 
  
    </div>
    <style>

.box{width:360px; text-align:center; font-szie:1px;}

.box img {width:10%;}

</style>
</body>
</html>

