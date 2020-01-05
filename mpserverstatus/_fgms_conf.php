<?php

/* #############################################################
   Set this to your site hosting this status info... thanks...
   #############################################################  */
$host_site = "localhost";

/* Set these variables to match your local fgms instance
$fgms_path = "/usr/local/sbin/fgms";
$fgms_conf = "/usr/local/etc/fgms.conf";
$fgms_log = "/var/log/fgms.log";*/

$tracker_ip=Array('206.81.5.202','www.fgprc.org');
$min_subversion=23;
$min_version=10;

// mpserver array; adjust as necessary
$mpserver_list = array(
	"mpcn01" => array(
		"short"	=> "mpcn01",
		"long"	=> "mpcn01.fgprc.org",
		"port"	=> "5001",
		"loc"	=> "Shanghai, China",
		"force_untracked" =>false
	),
	"mpcn02" => array(
		"short"	=> "mpcn02",
		"long"	=> "mpcn02.fgprc.org",
		"port"	=> "5001",
		"loc"	=> "NewYork, US",
		"force_untracked" =>false
	)
);

?>