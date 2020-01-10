<?="<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";?>
<?php

require_once("./_fgms_conf.php");
$HEAD = "";
$BLANKS = "";
$ONLOAD = "";

foreach($mpserver_list as $mpserver) {

	$ONLOAD .= "\n      check_mpserver(\"".$mpserver['short']."\");";

	$BLANKS .= <<<BLANKS
		<tr id="$mpserver[short]">
			<td id="$mpserver[short]_status" class="clearbox" style="font-size: 6pt; text-align: center; background-color: #d8d8d8;">
            正在检查......
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				$mpserver[long]
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				$mpserver[loc]
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
			<td class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
			<td id="$mpserver[short]_chk" class="clearbox" style="background-color: #d8d8d8;">
				...
			</td>
		</tr>

BLANKS;

}

$HEAD .= <<<HEAD
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
  <title>FGPRC Multiplayer Status</title>
  <link rel="stylesheet" href="./index.css" type="text/css" />
  <meta name="description" content="FGPRC Multilplayer Server Status Page" />
  <script src="./fg.js" type="text/javascript"></script>
  <script type="text/javascript">
    function check_mpservers() { $ONLOAD
    }
	setInterval('check_mpservers()',60000);
  </script>
</head>

<body onload="check_mpservers();">
	<table width="100%" border="0">
		<tr>
			<td colspan="9" class="clearbox">
				<div id="header">
					FGPRC 联机服务器状态<br />
				</div>
				<div class="title">
                Page started on 
HEAD;
$HEAD .= date("y-m-d G:i:s").'<br />
				</div>
				*"被记录" 说明该服务器被 <a href="https://www.fgprc.org">www.fgprc.org</a>, min. : v0.10.'.$min_subversion.'记录。
			</td>
		</tr>
		<tr class="header">
			<td class="clearbox">
                状态
			</td>
			<td class="clearbox">
				服务器地址
			</td>
			<td class="clearbox">
				服务器位置
			</td>
			<td class="clearbox">
				服务器 IP 地址
			</td>
			<td class="clearbox">
                版本
			</td>
			<td class="clearbox">
				被记录
			</td>
			<td class="clearbox">
				记录服务器 IP 地址
			</td>
			<td class="clearbox">
				客户端数量
			</td>
			<td class="clearbox">
				本地客户端
			</td>
			<td class="clearbox">
                最后检查时间
			</td>
		</tr>';

$FOOT .= <<<FOOT
	</table>
</body>
</html>

FOOT;


print $HEAD;
print $BLANKS;
print $FOOT;

?>
