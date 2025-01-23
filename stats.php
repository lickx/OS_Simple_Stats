<?php

$robustURL   = "yourgridurl"; //FQDN or IP to your grid/robust server
$robustPORT = "8002"; //port for your robust
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

if ($hguser = $mysqli->query("SELECT UserID, Login FROM GridUser WHERE UserID LIKE '%http%' AND Login > UNIX_TIMESTAMP() - 2592000"))
{
    if ($hguser->num_rows > 0)
        $preshguser = $hguser->num_rows / 2; // /2 because of double entried with a slash suffix
    else $preshguser = 0;
}

$nowonlinescounter = 0;
if ($preso = $mysqli->query("SELECT UserID FROM Presence")) {
    $nowonlinescounter = $preso->num_rows;
}
$pastmonth = 0;
if ($tpres = $mysqli->query("SELECT DISTINCT * FROM GridUser WHERE UserID NOT LIKE '%http%' AND Login > UNIX_TIMESTAMP() - 2592000")) {
    $pastmonth = $tpres->num_rows;
}
$totalaccounts = 0;
if ($useraccounts = $mysqli->query("SELECT * FROM UserAccounts")) {
    $totalaccounts = $useraccounts->num_rows - 1;
}
$totalregions = 0;
$totalvarregions = 0;
$totalsingleregions = 0;
$totalsize = 0;
if($regiondb = $mysqli->query("SELECT * FROM regions")) {
    while ($regions = $regiondb->fetch_array()) {
        if ($regions['sizeX'] == 256) {
            ++$totalsingleregions;
            $rsize = $regions['sizeX'] * $regions['sizeY'];
            $totalsize += $rsize / 1000;
            ++$totalregions;
        } else {
            ++$totalvarregions;
            $rsize = $regions['sizeX'] * $regions['sizeY'];
            $totalsize += $rsize / 1000;
            $totalregions += ($regions['sizeX'] / 256) * ($regions['sizeY'] / 256);
        }
    }
}

$avatardensity = $nowonlinescounter / $totalregions;

$arr = ['GridStatus' => $gstatus,
    'Online Now' => number_format($nowonlinescounter),
    'Live avatar density' => number_format($avatardensity,2) . ' avatars per region',
    'Registered users' => number_format($totalaccounts),
//    'HG_Visitors_Last_30_Days' => number_format($preshguser),
//    'Local_Users_Last_30_Days' => number_format($pastmonth),
    'Standard regions' => number_format($totalregions),
    'Unique 30-day visitors' => number_format($pastmonth + $preshguser),
//    'Var regions' => number_format($totalvarregions),
//    'Single regions' => number_format($totalsingleregions),
    'Land mass' => number_format($totalsize) . ' km2'
//    'Login URL' => $loginuri
];

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

