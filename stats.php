<?php

$website = "http://yourgridurl.xxx";
$loginscreen = "path_to_your_login_screen";
$robustURL   = "yourgridurl"; //FQDN or IP to your grid/robust server
$robustPORT = "8002"; //port for your robust
$website = "http://yourwebsiteurl.xxx";
$loginuri = "http://".$robustURL.":".$robustPORT."";
//your database info
$host = "localhost";
$user = "username";
$pass = "pass";
$dbname = "dbname";


// Online / Offline with socket
$socket = @fsockopen($robustURL, $robustPORT, $errno, $errstr, 1);
if (is_resource($socket))
{
$gstatus = "ONLINE";
$color = "green";
}
else {
$gstatus = "OFFLINE";
$color = "red";
}
@fclose($socket);



$mysqli = new mysqli($host,$user,$pass,$dbname);
$presenceuseraccount = 0;
$preshguser = 0;
if ($pres = $mysqli->query("SELECT * FROM Presence")) {
	while ($presrow = $pres->fetch_array()) {
		if ($luser = $mysqli->query("SELECT * FROM UserAccounts WHERE PrincipalID = '".$presrow['UserID']."'")) {
			++$presenceuseraccount;
		}else{
			++$preshguser;
		}
	}
}
$monthago = time() - 2592000;
$pastmonth = 0;
if ($tpres = $mysqli->query("SELECT * FROM GridUser WHERE Logout < '".$monthago."'")) {
	$pastmonth = $tpres->num_rows;
}
$totalaccounts = 0;
if ($useraccounts = $mysqli->query("SELECT * FROM UserAccounts")) {
	$totalaccounts = $useraccounts->num_rows;
}
$totalregions = 0;
$totalvarregions = 0;
$totalsingleregions = 0;
$totalsize = 0;
if($regiondb = $mysqli->query("SELECT * FROM regions")) {
	while ($regions = $regiondb->fetch_array()) {
		++$totalregions;
		if ($regions['sizeX'] == 256) {
			++$totalsingleregions;
		}else{
			++$totalvarregions;
		}
		$rsize = $regions['sizeX'] * $regions['sizeY'];
		$totalsize += $rsize;
	}
}
$arr = ['GridStatus' => '<b><font color="'.$color.'">'.$gstatus.'</b></font>',
	'InWorld' => number_format($presenceuseraccount),
	'HGVisitors' => number_format($preshguser),
	'MonthLogin' => number_format($pastmonth),
	'TotalAccounts' => number_format($totalaccounts),
	'Regions' => number_format($totalregions),
	'VarRegions' => number_format($totalvarregions),
	'SingleRegions' => number_format($totalsingleregions),
	'TotalLandSize' => number_format($totalsize),
	'Login URL' => $loginuri,
	'Website' => '<i><a href='.$website.'>'.$website.'</a></i>',
	'Login Screen' => '<i><a href='.$loginscreen.'>'.$loginscreen.'</a></i>'];
	
if ($_GET['format'] == "json") {
	header('Content-type: application/json');
	echo json_encode($arr);
}else if ($_GET['format'] == "xml") {
	function array2xml($array, $wrap='Stats', $upper=true) {
	    $xml = '';
	    if ($wrap != null) {
	        $xml .= "<$wrap>\n";
	    }
	    foreach ($array as $key=>$value) {
	        if ($upper == true) {
	            $key = strtoupper($key);
	        }
	        $xml .= "<$key>" . htmlspecialchars(trim($value)) . "</$key>";
	    }
	    if ($wrap != null) {
	        $xml .= "\n</$wrap>\n";
	    }
	    return $xml;
	}
	header('Content-type: text/xml');
	print array2xml($arr);
}else{
	foreach($arr as $k => $v) {
		echo '<B>'.$k.': </B>'.$v.'<br>';
	}

	}
$mysqli->close();
?>