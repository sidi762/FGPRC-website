<?php
class fgt_msg_process
{
	var $uuid;
	var $open_flight_array;
	
	function __construct ($uuid)
	{
		global $fgt_error_report,$clients,$var,$fgt_sql;
		$this->uuid=$uuid;
		
		/*Get opening flights*/
		$sql="select flights.id,callsign,start_time, count(waypoints.flight_id) as cnt from flights left join waypoints on waypoints.flight_id=flights.id where status='OPEN' AND (server='".$clients[$this->uuid]['server_ident'] ."' or server is NULL) group by flights.id, callsign, start_time order by callsign, start_time";
		$res=pg_query($fgt_sql->conn,$sql);
		if ($res===false or $res==NULL)
		{
			$message="Message processor for ".$clients[$this->uuid]['server_ident'] ." could not be initialized due to DB problem - ".pg_last_error ($fgt_sql->conn);
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			$clients[$uuid]['connected']=false;
			return;
		}
		$nr=pg_num_rows($res);
		for ($i=0;$i<$nr;$i++)
		{
			$this->open_flight_array[pg_result($res,$i,"callsign")]['id']=pg_result($res,$i,"id");			
			$this->open_flight_array[pg_result($res,$i,"callsign")]['waypoints']=pg_result($res,$i,"cnt");			
		}


		pg_free_result($res);
		$message="Message processor for ".$clients[$this->uuid]['server_ident'] ." initalized. $nr flight(s) remain open";
		$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_NOTICE);			
	}
	
	function computeDistance($latA,$lonA,$latB,$lonB)
	{	/* Compute the distance between two waypoints (meters)*/
		$ERAD=6378138.12; 
		$D2R = M_PI / 180;
		$R2D = 180 / M_PI;

		$latA*=$D2R;
		$lonA*=$D2R;
		$latB*=$D2R;
		$lonB*=$D2R;
		
		$distance=$ERAD*2*asin(sqrt(pow(sin(($latA-$latB)/2),2) + cos($latA)*cos($latB)*pow(sin(($lonA-$lonB)/2),2)));

		return $distance;
	}
	
	function fgt_pg_query_params($sql,$sql_parm)
	{
		global $fgt_error_report,$clients,$fgt_sql;
		$res=pg_query_params($fgt_sql->conn,$sql,$sql_parm);
		if ($res===false or $res==NULL)
		{
			$phpErr=error_get_last();
			$message="Internal DB Error - ".pg_last_error ($fgt_sql->conn);
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			$message="SQL command of last error: ".$sql;
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			$message="PHP feedback of last error: ".$phpErr['message'];
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			$stat = pg_connection_status($fgt_sql->conn);
			if ($stat !== PGSQL_CONNECTION_OK) 
				$fgt_sql->connected=false;
			$clients[$this->uuid]['connected']=false;
			return false;
		}return $res;
	}
	
	function msg_start()
	{	
		global $fgt_error_report,$clients,$fgt_sql;
		if($fgt_sql->inTransaction===true)
		{
			$message="SQL TRANSACTION is called more than once";
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			$fgt_sql->connected=false;
			$clients[$this->uuid]['connected']=false;
			return false;
		}
		/*start a transaction*/
		$sql="START TRANSACTION;";
		$res=$this->fgt_pg_query_params($sql,Array());
		if ($res===false or $res==NULL)
		{
			$message="Failed to start SQL TRANSACTION - ".pg_last_error ($fgt_sql->conn);
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			return false;
		}	
		$fgt_sql->inTransaction=true;
		return true;
	}
	
	function msg_process($msg_array,$uuid)
	{
		global $fgt_error_report,$clients,$var,$fgt_sql;
		if($fgt_sql->inTransaction===FALSE)
		{
			$clients[$this->uuid]['connected']=false;
			$message="Internal Error: msg_process is called without calling msg_start";
			$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ERROR);
			return false;
		}

		/*process message*/
		switch ($msg_array['nature'])
		{
			case "POSITION":
				$err_prefix="Could not insert POSITION for callsign \"".$msg_array['callsign']."\" from ".$clients[$this->uuid]['server_ident'].".";
				if(!isset($this->open_flight_array[$msg_array['callsign']]))
				{
					$message="$err_prefix No open flight available";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_NOTICE);	
					break;
				}
				if(intval($msg_array['alt'])<-9000)
				{
					$message="$err_prefix Invalid altitude (".$msg_array['alt'].")";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_WARNING);
					break;
				}
				if(intval($msg_array['alt'])==0 and intval($msg_array['lat'])==0 and intval($msg_array['lon'])==0)
				{
					$message="$err_prefix Invalid position (".$msg_array['lat'].", ".$msg_array['lon'].", ".$msg_array['alt'].")";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_WARNING);
					break;
				}
				$timestamp=$msg_array['date']." ".$msg_array['time']." Z";
				/*TODO: Use UPSERT once PostgreSQL 9.5 is available*/
				if(!isset($msg_array['heading']))
					$msg_array['heading']=null;
				$sql_parm=Array($this->open_flight_array[$msg_array['callsign']]['id'],$timestamp,$msg_array['lat'],$msg_array['lon'],$msg_array['alt'],$msg_array['heading']);
				$sql="INSERT INTO waypoints(flight_id,time,latitude,longitude,altitude,heading)VALUES ($1,$2,$3,$4,$5,$6);";
				
				$res=$this->fgt_pg_query_params($sql,$sql_parm);
				if ($res===false or $res==NULL)
					return false;
				$this->open_flight_array[$msg_array['callsign']]['waypoints']++;
				
				/* Splited flight detection
				A newly started flight will be checked when the second waypoints received.
				In case the following situation occurs, flight merging will automatically be done:
				1. Same model as previous flight
				2. Start_time and previous end_time 0<=time<=120 seconds 
				3. First waypoints and last waypoints distance <408m/s
				4. First two waypoints of current flight have the calculated speed >60km/h
				5. Last two waypoints of previous flight have the calculated speed >60km/h
				*/
				if($this->open_flight_array[$msg_array['callsign']]['waypoints']!=2)
					break;
				$merge_speed_threshold=60; /*in KM/h*/
				$flightid=$this->open_flight_array[$msg_array['callsign']]['id'];
				$message="Splited flight detection on callsign ".$msg_array['callsign'];
				$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ALL);
				$sql_parm=Array($msg_array['callsign'],$flightid);
				$sql="select * from flights where callsign=$1 and (select extract(epoch from start_time) from flights where id=$2)-extract(epoch from end_time) <120 and (select extract(epoch from start_time) from flights where id=$2)- extract(epoch from end_time) > 0 and model=(select model from flights where id=$2) order by end_time desc limit 1";
				$res=$this->fgt_pg_query_params($sql,$sql_parm);
				if ($res===false or $res==NULL)
					return false;
				$nr=pg_num_rows($res);
				if($nr!=1)
					break;

				$p_flightid=pg_result($res,0,"id");
				$message="First phase pass on callsign ".$msg_array['callsign']." (Previous flight id: $p_flightid). Conduct second phase check";
				$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ALL);
				$sql_parm=Array($flightid,$p_flightid);
				$sql='(select flight_id, extract(epoch from "time") AS time, latitude, longitude from waypoints where flight_id=$1 order by time desc) UNION all (select flight_id, extract(epoch from "time") AS time, latitude, longitude from waypoints where flight_id=$2 order by time desc offset 1 limit 2)';						
				$res=$this->fgt_pg_query_params($sql,$sql_parm);
				if ($res===false or $res==NULL)
					return false;
				$nr=pg_num_rows($res);
				if ($nr!=4)
				{
					$message="Splited flight second phase check failed on callsign ".$msg_array['callsign']." - No. of wpts not equal to 4 (got $nr)";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_WARNING);
					break;
				}
				for($i=0;$i<$nr;$i++)/*waypoints in time descending order*/
					$waypointsArr[]=Array('time'=>pg_result($res,$i,"time"),'lat'=>pg_result($res,$i,"latitude"),'lon'=>pg_result($res,$i,"longitude"));
				pg_free_result($res);
				
				/*check time validity*/
				if($waypointsArr[0]['time']==$waypointsArr[1]['time'] or $waypointsArr[2]['time']==$waypointsArr[3]['time'])
				{
					$message="Splited flight second phase check failed on callsign ".$msg_array['callsign']." - Invalid time detected";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_WARNING);
					break;					
				}
				/*check if wpt 0&1 over 60km/h*/
				$distanceM=$this->computeDistance($waypointsArr[0]['lat'],$waypointsArr[0]['lon'],$waypointsArr[1]['lat'],$waypointsArr[1]['lon']);
				$speedKmh=abs($distanceM/($waypointsArr[0]['time']-$waypointsArr[1]['time'])*3600/1000); 
				if($speedKmh<$merge_speed_threshold)
				{
					$message="Splited flight second phase check failed on callsign ".$msg_array['callsign']." - Current flight is slower than threshold (got $speedKmh)";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ALL);
					break;
				}
				/*check if wpt 2&3 over 60km/h*/
				$distanceM=$this->computeDistance($waypointsArr[2]['lat'],$waypointsArr[2]['lon'],$waypointsArr[3]['lat'],$waypointsArr[3]['lon']);
				$speedKmh=abs($distanceM/($waypointsArr[2]['time']-$waypointsArr[3]['time'])*3600/1000); 
				if($speedKmh<$merge_speed_threshold)
				{
					$message="Splited flight second phase check failed on callsign ".$msg_array['callsign']." - Previous flight is slower than threshold (got $speedKmh)";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ALL);
					break;
				}
				/*check if First waypoints and last waypoints distance > 408m/s*/
				$distanceM=$this->computeDistance($waypointsArr[1]['lat'],$waypointsArr[1]['lon'],$waypointsArr[2]['lat'],$waypointsArr[2]['lon']);
				$distanceThreshold=408*($waypointsArr[1]['time']-$waypointsArr[2]['time']);
				if($distanceM>$distanceThreshold)
				{
					$message="Splited flight second phase check failed on callsign ".$msg_array['callsign']." - Distance between departure and arriaval greater than threshold (Should smaller than $distanceThreshold but got $distanceM)";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_ALL);
					break;
				}
				
				$message="Splited flight second phase check pass on callsign ".$msg_array['callsign']." - Perform merging flight id $flightid to $p_flightid";
				$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_NOTICE);
				
				/*update waypoints and no of wpts. Delete Effective_flight_time,start_icao, end_icao. Set status to open if the flight still active*/
				$sql_parm=Array($p_flightid,$clients[$this->uuid]['server_ident']);
				$sql2="update flights set end_time=NULL, effective_flight_time=NULL,start_icao=NULL, end_icao=NULL,status='OPEN', server=$2 where id=$1;";
				pg_query_params($fgt_sql->conn,$sql2,$sql_parm);
				
				$sql_parm=Array($p_flightid,$flightid);
				$sql3="update waypoints set flight_id=$1 where flight_id=$2;";
				pg_query_params($fgt_sql->conn,$sql3,$sql_parm);
				
				/*delete flight*/
				$sql_parm=Array($flightid);
				$sql4="delete from flights where id=$1;";
				pg_query_params($fgt_sql->conn,$sql4,$sql_parm);
				
				$sql_parm=Array("FGTracker",$msg_array['callsign'],$clients[$this->uuid]['server_ident']);
				$sql5="INSERT into log (username,\"table\",action,\"when\",callsign,usercomments,flight_id,flight_id2) VALUES ($1, 'flights', 'Auto merge flight $flightid to $p_flightid', NOW(), $2,$3,$p_flightid,$flightid);";
				pg_query_params($fgt_sql->conn,$sql5,$sql_parm);
				
				$this->open_flight_array[$msg_array['callsign']]['id']=$p_flightid;
			break;
			case "CONNECT":
				$err_prefix="Could not CONNECT for callsign \"".$msg_array['callsign']."\" from ".$clients[$this->uuid]['server_ident'].".";
				
				if($var['selective_callsign_tracking']===true and !(strpos($msg_array['callsign'],"_TW") ==4 and strlen($msg_array['callsign'])==7))
				{	/*check if callsign exist in callsigns table*/
					$sql_parm=Array($msg_array['callsign']);
					$sql="select * from callsigns where callsign=$1 and (activation_level>0 or (activation_level=0 and Now()-reg_time < INTERVAL '3 days'))";
					$res=$this->fgt_pg_query_params($sql,$sql_parm);
					if ($res===false or $res==NULL)
						return false;
					if(pg_num_rows($res)<=0)
					{
						$message="$err_prefix Callsign not registered or not activated.";
						$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_WARNING);
						break;	
					}
				}
				
				if(isset($this->open_flight_array[$msg_array['callsign']]))
				{
					/*Close previous flight*/
					$sql="select time from waypoints where flight_id=".$this->open_flight_array[$msg_array['callsign']]['id']." ORDER BY time DESC LIMIT 1";
					$res=$this->fgt_pg_query_params($sql,Array());
					if ($res===false or $res==NULL)
						return false;
					
					if(pg_num_rows($res)!=1)
						$close_time=NULL;
					else
						$close_time=pg_result($res,0,"time");
					pg_free_result($res);

					if($close_time==NULL)
					{
						$sql_parm=Array($this->open_flight_array[$msg_array['callsign']]['id']);
						$sql="UPDATE flights SET status='CLOSED',end_time=start_time WHERE id=$1;";
					}else
					{
						$sql_parm=Array($close_time,$this->open_flight_array[$msg_array['callsign']]['id']);
						$sql="UPDATE flights SET status='CLOSED',end_time=$1 WHERE id=$2;";						
					}

					$res=$this->fgt_pg_query_params($sql,$sql_parm);
					if ($res===false or $res==NULL)
						return false;
				}

				/*Insert flight*/
				$timestamp=$msg_array['date']." ".$msg_array['time']." Z";
				$sql_parm=Array($msg_array['callsign'],$msg_array['model'],$timestamp,$clients[$this->uuid]['server_ident']);
				$sql="INSERT INTO flights (callsign,status,model,start_time,server) VALUES ($1,'OPEN',$2,$3,$4);";
				$res=$this->fgt_pg_query_params($sql,$sql_parm);
				if ($res===false or $res==NULL)
					return false;
				
				
				$res=pg_query($fgt_sql->conn,"SELECT currval('flights_id_seq') AS lastinsertid;");
				$this->open_flight_array[$msg_array['callsign']]['id']=pg_result($res,0,"lastinsertid");
				$this->open_flight_array[$msg_array['callsign']]['waypoints']=0;
				pg_free_result($res);
				
				$message="Welcome callsign \"".$msg_array['callsign']."\" with flight id ".$this->open_flight_array[$msg_array['callsign']]['id'];
				$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_NOTICE);
			break;
			case "DISCONNECT":
				$err_prefix="Could not DISCONNECT for callsign \"".$msg_array['callsign']."\" from ".$clients[$this->uuid]['server_ident'].".";
				$timestamp=$msg_array['date']." ".$msg_array['time']." Z";
				if(!isset($this->open_flight_array[$msg_array['callsign']]))
				{
					$message="$err_prefix No open flights available.";
					$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_WARNING);
					break;
				}
				$sql_parm=Array($timestamp,$this->open_flight_array[$msg_array['callsign']]['id']);
				$sql="UPDATE flights SET status='CLOSED',end_time=$1 WHERE id=$2 AND status='OPEN';";
				$res=$this->fgt_pg_query_params($sql,$sql_parm);
					if ($res===false or $res==NULL)
						return false;
				
				$message="Callsign \"".$msg_array['callsign']."\" from ".$clients[$this->uuid]['server_ident']." with flight id ".$this->open_flight_array[$msg_array['callsign']]['id']." left";
				$fgt_error_report->fgt_set_error_report($clients[$this->uuid]['server_ident'],$message,E_NOTICE);
				unset($this->open_flight_array[$msg_array['callsign']]);
			break;
			default:
			/*fgt_read_XX should already handled the unrecognized messages*/
			return;
		}
		return true;
	}
	
	function msg_end($packet)
	{	
		global $fgt_error_report,$clients,$fgt_sql,$var;
		$sql="COMMIT;";
		$res=$this->fgt_pg_query_params($sql,Array());
		if ($res===false or $res==NULL)
		{
			pg_query_params($fgt_sql->conn,"rollback;",Array());
			$fgt_sql->inTransaction=false;
			
			/*Block this server from reconnecting until problem fixed*/
			$sql="UPDATE fgms_servers SET enabled=FALSE where name=$1";
			pg_query_params($fgt_sql->conn,$sql,Array($clients[$this->uuid]['server_ident']));
			
			$email_content="[".date('Y-m-d H.i.s')."] During the process of the following packet:
			===============================================
			$packet
			===============================================
			
			Error occured and here below is the message dump:
			$message_dump
			
			FGTracker
			";
			$email_content=str_replace("\n","\r\n", $email_content);
			$email_title=$clients[$this->uuid]['server_ident']." is blocked due to SQL Error";
			if ($var['error_email_send']===true)
				$fgt_error_report->send_email( $email_title, $email_content);
			
			$sql_parm=Array("FGTracker",$email_title,$email_content);
			$sql="INSERT into log (username,\"table\",action,\"when\",callsign,usercomments,flight_id,flight_id2) VALUES ($1, NULL, $2, NOW(), NULL,$3,NULL,NULL);";
			pg_query_params($fgt_sql->conn,$sql,$sql_parm);
			$clients[$this->uuid]['connected']=false;
			return false;
		}
		$fgt_sql->inTransaction=false;
		return true;
	}
	
	function rollback()
	{
		global $fgt_sql;
		pg_query_params($fgt_sql->conn,"rollback;",Array());
		$fgt_sql->inTransaction=false;
	}
	
}
?>