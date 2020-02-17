<?php
#
#
#	This file contains the common variables and functions required for
#	the WiPi web front end file and parameter handling
#
#
require 'utilities.php';
#

function getmyhostname()
{
$hostnamefile = '/etc/hostname';
#
#	Get the hostname from the appropriate config file
#
return (rtrim(file_get_contents($hostnamefile)));
}

function updatemyhostname($name)
{
$hostnamefile = '/etc/hostname';
#
#	Update the Host name speaker name in the file
#
if (getmyhostname($hostnamefile) !== $name)
{
  if (file_put_contents($hostnamefile, $name."\n") !== FALSE)
  {
  return (TRUE);
  }
  else
  {
  return (FALSE);
  }
}
else
{
  return (FALSE);
}
}

function getWiPi($primekey)
{
$WiPiPowerfile = '/etc/power.conf';
  return getmykey($WiPiPowerfile,$primekey);
}

function updateWiPi($primekey, $name)
{
$WiPiPowerfile = '/etc/power.conf';
  return updatemykey($WiPiPowerfile,$primekey,$name);
}

function getWiPiname() {return getWiPi('NAME=');}
function getWiPidebug() {return getWiPi('DEBUG=');}
function getWiPiopmode() {return getWiPi('MODE=');}
function getWiPibluetooth() {return getWiPi('BLUE=');}

function updateWiPiname($name){return updateWiPi('NAME=',$name);}
function updateWiPidebug($name){return updateWiPi('DEBUG=',$name);}
function updateWiPiopmode($name){return updateWiPi('MODE=',$name);}
function updateWiPibluetooth($name){return updateWiPi('BLUE=',$name);}

function getWiPisensor() {return getWiPi('SENSOR=');}
function updateWiPisensor($name){return updateWiPi('SENSOR=',$name);}


function getNetwork($primekey)
{
$WiPiHeatfile = '/etc/network/interfaces';
  return getmykey($WiPiHeatfile,$primekey);
}

function updateNetwork($primekey, $name)
{
$WiPiHeatfile = '/etc/network/interfaces';
  return updatemykey($WiPiHeatfile,$primekey,$name);
}

function getTxPower() {return getNetwork('wireless-txpower ');}
function updateTxPower($value){return updateNetwork('wireless-txpower ',$value);}

function extractBSSid($string,$key)
{
#
#	Extract the BDDid which may or may not be present in string
#
  $o = strpos($string, $key);
  if ($o) {
    $x = $o+strlen($key);
    $y = $x+17;
    return substr($string, $x, $y-$x);
  } else {
    return "";
  }
}

function updateBSSid($string,$key,$name)
{
#
#	Update the BSSid, add to end as we use template
#
  $o = strpos($string, $key);

  if ($o) {
    if ($name == "") {
       $newname = "";
    } else {
       $newname = $key . $name;
    }
    $y = $o + strlen($key)+17;
    return substr_replace($string, $newname, $o+1, $y-$o);
  } else {
    if ($name == "") {
       return $string;
    } else {
       $o = strpos($string, "}");
       $newname = $key . $name . "\n";
       return substr_replace($string, $newname, $o, 0);
    }
  }
}

function getnetworknames()
{
$networkfile = '/etc/wpa_supplicant/wpa_supplicant.conf';
$primekey = 'network={';
$key1 = 'ssid=';
$key2 = 'psk=';
$key3 = 'bssid=';
#
#	Get the WiFi network parameters defined by key1 & key2
#
  $filedata = file_get_contents($networkfile);
  if ($filedata !== FALSE)
  {
    $position = strpos($filedata, $primekey);
    if ($position !== FALSE)
    {
      $networks = explode($primekey, $filedata);
      $n = count($networks);
      $i = 1;
      while ($i !== $n)
      {
        $output[3*($i-1)] = extractstring($networks[$i],$key1);
        $output[(3*($i-1))+1] = extractstring($networks[$i],$key2);
        $output[(3*($i-1))+2] = extractBSSid($networks[$i],$key3);
        $i = $i+1;
       }
    }
    else
    {
      $output = "no networks";
    }
  }
  else
  {
  $output = "no file";
  }
  return $output;
}

function updatenetworknames($nets)
{
$networkfile = '/etc/wpa_supplicant/wpa_supplicant.conf';
$primekey = 'network={';
$key1 = 'ssid=';
$key2 = 'psk=';
$key3 = 'bssid=';
#
#	Update the WiFi network parameters defined by key1 & key2
#
if (getnetworknames() !== $nets)
{

  $filedata = file_get_contents($networkfile);
  if ($filedata !== FALSE)
  {
    $position = strpos($filedata, $primekey);
    if ($position !== FALSE)
    {
      $networks = explode($primekey, $filedata);
#	Clear down the old array after default entry 
      $i = 3;
      $n = count($networks);
      while ($i !== $n)
      {
        unset($networks[$i]);
        $i=$i+1;
      }
#	Then rebuild the array using default entry as template
      $i = 0;
      $n = count($nets)/3;
      while ($i !== $n)
      {
        $i = $i+1;
        $networks[$i] = updatestring($networks[1],$key1,$nets[3*($i-1)]);
        $networks[$i] = updatestring($networks[$i],$key2,$nets[3*($i-1)+1]);
        $networks[$i] = updateBSSid($networks[$i],$key3,$nets[3*($i-1)+2]);
       }
       $output = implode($primekey, $networks);
	  if (file_put_contents($networkfile, $output) === FALSE)
       {
#	Write error
	  echo "<font color='Red'>Write failed - check permissions<font color='Black'>", "<br><br>";
          return(FALSE);
       }
    }
    else
    {
#	No network data
	return(FALSE);
    }
  }
  else
  {
#	No file
	return(FALSE);
  }
  return (TRUE);
}
else
{
return (FALSE);
}
}

function addnetwork(&$net,&$count,$add)
{
#
#	Add or delete a Wi-Fi network entry
#
if ($add)
{
$net[(3*$count)]="";
$net[(3*$count)+1]="";
$net[(3*$count)+2]="";
$count=$count+1;
}
else
{
$count=$count-1;
unset($net[(3*$count)]);
unset($net[(3*$count)+1]);
unset($net[(3*$count)+2]);
}
}

#
#	Get Network File share from fstab
#
function getnetwork_share(&$host, &$share) {

$drivefile = '/etc/fstab';
$key1 = "//";
$key2 = "/";
$key3 = "/mnt";
$key4 = "user=\"";
$key5 = "\"";
$key6 = "password=\"";
$key7 = "\"";$key8 = "0 0";
#
#	Extract the network drive details from /etc/fstab
#
   $host = "";
   $share = "";
   $user = "";
   $password = "";
#
   $filedata = file_get_contents($drivefile);
   if ($filedata !== FALSE){
     $a = strpos($filedata, $key1);
     if ($a !== FALSE) {
#	Having found the entry we parse the line assuming all components exist
        $a = $a + strlen($key1);
        $b = strpos($filedata, $key2, $a);
        $host =     substr($filedata, $a, $b-$a);
	$host =	    str_replace(".local", "", $host);
        $b = $b + strlen($key2);
        $c = strpos($filedata, $key3, $b);
        $share =    substr($filedata, $b, $c-$b);
        $share =    str_replace("\\040", " ", $share);
        $d = strpos($filedata, $key4, $c);
        $d = $d + strlen($key4);
        $e = strpos($filedata, $key5, $d);
        $user =     substr($filedata, $d, $e-$d);
        $f = strpos($filedata, $key6, $e);
        $f = $f + strlen($key6);
        $g = strpos($filedata, $key7, $f);
        $password = substr($filedata, $f, $g-$f);
        return(TRUE);
     } else {
        return(FALSE);
     }
   } else {
     echo "<font color='Red'>File Missing - check permissions<font color='Black'>", "<br><br>";
     return(FALSE);
   }
}
function updatenetwork_share($delete, $host, $share)
{
$drivefile = '/etc/fstab';
$key1 = "//";
$key2 = "/";
$key3 = "/mnt";
$key4 = "user=\"";
$key5 = "\"";
$key6 = "password=\"";
$key7 = "\"";
$key8 = "0 0";
$key9 = "#";
#
$user = "";
$password = "";
#
#	Update the network drive details from /etc/fstab
#
   $filedata = file_get_contents($drivefile);
   if ($filedata !== FALSE){
#	Contruct output line
     $line = "";
     $share =    str_replace(" ", "\\040", $share);
     if ($delete !== TRUE) {$line = sprintf("//%s.local/%s /mnt/network cifs vers=1.0,user=\"%s\",password=\"%s\",users,rw,file_mode=0777,dir_mode=0777 0 0\n", $host, $share, $user, $password); };
#	Identify locations for replace
     $a = strpos($filedata, $key1);
     if ($a !== FALSE) {
#	Having found the entry we parse the line assuming all components exist
        $a = $a;
        $b = strpos($filedata, $key8, $a);
        $b = $b + strlen($key8) + 1;
     } else {
        $a = strpos($filedata, $key9);
        $b = $a;
     }
     $filedata = substr_replace($filedata, $line, $a, $b-$a);
     if (file_put_contents($drivefile, $filedata) === FALSE)
     {
        echo "<font color='Red'>Write failed - check permissions<font color='Black'>", "<br><br>";
        return(FALSE);
     }
   } else {
     echo "<font color='Red'>File Missing - check permissions<font color='Black'>", "<br><br>";
     return(FALSE);
   }
}

#
#	Get Power Usage from the Logfile on the Master Node
#
function get_power_usage($node, $selected_date, &$status) {

    $logfile = '/mnt/storage/Power/'.$node.'_'.$selected_date.'.csv';
    if (file_exists($logfile)) {
	$csv = array_map('str_getcsv', file($logfile));

	$timestamp = array_column($csv, 0);
	$power = array_column($csv, 1);
	$power_usage = array_sum($power)/12;

	if (time_to_decimal(end($timestamp)) >=  time_to_decimal("23:55")) {
	    $status = 2;
	    return($power_usage);
	} else {
	    $status = 1;
	    return($power_usage);
	}
    }
    $status = 0;
    return(0);
}

#
#
#	Get data from MASTER Log File
#
function get_log_info($node, $selected_date, $column) {

    $logfile = '/mnt/storage/Power/'.$node.'_'.$selected_date.'.csv';
    if (file_exists($logfile)) {
	$csv = array_map('str_getcsv', file($logfile));

        $info = array_column($csv, $column);
	return(end($info));
    }
    return("data not available");
}

function get_logtime($node, $selected_date) {
    return(get_log_info($node, $selected_date,0));
}
function get_logstate_zone($node, $selected_date, $zone) {
    return(get_log_info($node, $selected_date,$zone+2));
}
function get_logstate_at_home($node, $selected_date) {
    return(get_log_info($node, $selected_date,4));
}
function get_logstate_network($node, $selected_date) {
    return(get_log_info($node, $selected_date,5));
}

#
#
#
function processrestart($name)
{
#
#	Request shutdown deamon to restart a server process 
#
      $tmp = "/var/www/restart.tmp";
      $file = "/var/www/restart." .  $name . "-restart";
#      echo "WiPi Restart request", " using ", $file, "<br><br>";
      file_put_contents($tmp, "Please restart\n");
      $p = rename($tmp, $file);
      if ($p === FALSE) echo "<font color='Red'>Write failed - check permissions<font color='Black'>", "<br><br>";
}

function requestrestart($shut)
{
#
#	Ask Shutdown.py to  Reboot Pi
#
      $tmp = "/var/www/restart.tmp";
      $file = ($shut ? "/var/www/restart.force-restart" : "/var/www/restart.force-shutdown");
#      echo "WiPi Restart request", " using ", $file, "<br><br>";
      file_put_contents($tmp, "Please restart\n");
      $p = rename($tmp, $file);
      if ($p === FALSE) echo "<font color='Red'>Write failed - check permissions<font color='Black'>", "<br><br>";
}

function time_to_decimal($time) {
    $timeArr = explode(':', $time);
    $decTime = (($timeArr[0]*60) + ($timeArr[1]))/60;

    return $decTime;
}
?>
