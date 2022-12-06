<?php

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
    @fclose($socket);
}
else {
    $gstatus = "OFFLINE";
    $color = "red";
}

$mysqli = new mysqli($host,$user,$pass,$dbname);
$presenceuseraccount = 0;

$monthago = time() - 2592000; 

if ($hguser = $mysqli->query("SELECT UserID, Login FROM GridUser WHERE UserID LIKE '%htt%' AND Login < 'time() - 2592000'")) 
{
    $preshguser= $hguser->num_rows;
}

$nowonlinescounter = 0;
if ($preso = $mysqli->query("SELECT UserID FROM Presence")) {
    $nowonlinescounter = $preso->num_rows;
}
$pastmonth = 0;
if ($tpres = $mysqli->query("SELECT DISTINCT * FROM GridUser WHERE UserID NOT LIKE '%http%' AND Login < 'time() - 2592000'")) {
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
        } else {
            ++$totalvarregions;
        }
        $rsize = $regions['sizeX'] * $regions['sizeY'];
        $totalsize += $rsize / 1000;
    }
}

$arr = ['GridStatus' => '<b><font color="'.$color.'">'.$gstatus.'</b></font>',
    'Online_Now' => number_format($nowonlinescounter),
    'HG_Visitors_Last_30_Days' => number_format($preshguser),
    'Local_Users_Last_30_Days' => number_format($pastmonth),
    'Total_Active_Last_30_Days' => number_format($pastmonth + $preshguser),
    'Registered_Users' => number_format($totalaccounts),
    'Regions' => number_format($totalregions),
    'Var_Regions' => number_format($totalvarregions),
    'Single_Regions' => number_format($totalsingleregions),
    'Total_LandSize(km2)' => number_format($totalsize),
    'Login_URL' => $loginuri,
    'Website' => '<i><a href='.$website.'>'.$website.'</a></i>',
    'Login_Screen' => '<i><a href='.$loginscreen.'>'.$loginscreen.'</a></i>'];

if (isset($_GET['format']) && $_GET['format'] == "json") {
    header('Content-type: application/json');
    echo json_encode($arr);
} else if (isset($_GET['format']) && $_GET['format'] == "xml") {
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
} else {
    echo "<!DOCTYPE html>\n<html>\n<head>\n<title>Mygrid - Statistics</title>\n" .
         "<link rel=\"stylesheet\" href=\"style.css\">\n</head>\n<body>\n";
    foreach($arr as $k => $v) {
        echo '<strong>'.$k.': </strong>'.$v."<br>\n";
    }
    echo "</body>\n</html>";
}
$mysqli->close();
?>

