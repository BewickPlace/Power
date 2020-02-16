<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<?php
require 'html_functions.php';
require 'functions.php';
$hostname = getmyhostname();
print("<title>".$hostname.": Device</title>");
?>
<link rel="stylesheet" type="text/css" href="style.css">
</head>

<?php
#
#	Define local variables
#
$readform = "readonly";
$changesmade = FALSE;
$wirelessmode = "";
?>

<body>
<div id="page">
 <div id="header">
<?php
#
#	Header section of the page
#
?>
 <h1> WiPi-Power:  <?php echo $hostname ?></h1>
 </div>

<?php
require 'manage_menu.php';
?>

 <div id="body">

<?php
#
#	WiPi-Power configuration form
#
?>
  <h2>WiPi-Power Network  Configuration:</h2>
  <p>

  <?php
#
#	Obtain configuration data from files or returned POST
#
#
#  var_dump($_POST);

  $remotename = "";

     if ((isset($_POST["submit"]))
	AND (($_POST["submit"] === "Edit Values")
	OR ($_POST["submit"] === "Add")
	OR ($_POST["submit"] === "Delete")
	OR ($_POST["submit"] === "Apply Changes"))) {
    $hostname = test_input($_POST["hostname"]);

    $i = 0;
    foreach($_POST["networks"] as $networks[$i])
    {
    $networks[$i] = test_input($networks[$i]);
    $i = $i+1;
    }
    $numberofnetworks = count($networks)/3;

    switch ($_POST["submit"])
    {
    case "Edit Values":
      $readform = "";
	 break;
    case "Add":
      $readform = "";
      $numberofnetworks = count($networks)/3;
      addnetwork($networks,$numberofnetworks,TRUE);
	 break;
    case "Delete":
      $readform = "";
      $numberofnetworks = count($networks)/3;
      addnetwork($networks,$numberofnetworks,FALSE);
	 break;
    case "Apply Changes":

      $up1 = updatemyhostname($hostname);
      $up2 = updatenetworknames($networks);

      if ($up1 or $up2) $changesmade = TRUE;
	 break;
    }
  }
  else
  {
    $hostname = getmyhostname();
    $hostIPaddress = $_SERVER['SERVER_ADDR']; 
    $networks = getnetworknames();


    if(isset($_POST['submit'])) {
    switch ($_POST["submit"])
    {
    case "Shutdown WiPi-Power":
      ?><script>window.location.href = "wait.php";</script><?php
      requestrestart(FALSE);
	 break;
    case "Restart WiPi-Power":
      ?><script>window.location.href = "wait.php?page=index.php";</script><?php
      requestrestart(TRUE);
	 break;
    case "Restart WiFi Network":
      processrestart("network");
	 break;
    case "Restart Power App":
      processrestart("power");
	 break;
    }
    }
    if(isset($_POST['txpower'])) {
	updatetxpower(test_input($_POST["txpower"]));
	$changesmade = TRUE;
    }
  }
  $numberofnetworks = count($networks)/3;
  $hostIPaddress = $_SERVER['SERVER_ADDR'];
  ?>

  <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" autocomplete="off">
   Host name: <input type="text" name="hostname" value=<?php echo $hostname?> <?php echo $readform ?> size=14 maxlength=12  pattern="[a-zA-Z0-9_-]+" required title="Alphanumeric and - or _"> at IP address: <?php echo $hostIPaddress ?><br>

    <br><br>
   Wi-Fi Configuration Details:    <br><br>
   <?php
   $i = 0;
   $r = "readonly";
   $t = "password";
   While ($i < $numberofnetworks)
   {
   ?>
   <input type = "hidden" name="menuselect" value=,<?php echo $menu_mode ?>>
   SSID:      <input type="text" name="networks[<?php (3*$i) ?>]" value="<?php echo $networks[(3*$i)]?>" <?php echo $r ?> size=22 maxlength=20 pattern="[a-zA-Z0-9 _-]+" required title="Alphanumeric and -_ or space">
   Password:  <input type=<?php echo $t?> name="networks[<?php (3*$i)+1 ?>]" value="<?php echo $networks[(3*$i)+1]?>" <?php echo $r ?> size=12 maxlength=10  pattern="[a-zA-Z0-9]+" required title="Alphanumeric">
   <?php
   $bssid = $networks[(3*$i)+2];
   #  Only perform for the non-default networks
   if ($i > 0) {
   ?>
      BSSID: <input tpe="text" name="networks[<?php (3*$i)+2 ?>]" value="<?php echo $networks[(3*$i)+2]?>" <?php echo $r ?> size=18 maxlength=17 pattern="^([0-9A-F]{2}[:-]){5}([0-9A-F]{2})$"  title="MAC address">
   <?php
   } else {
   ?>
      <input type="hidden" name="networks[<?php (3*$i)+2 ?>]" value="<?php echo $networks[(3*$i)+2]?>">
   <?php
      echo "default network (not editable)";
      $r = $readform;
      if (!$readform) $t="text";
   }
   ?>
    <br>
   <?php
   $i = $i+1;
   }
   if ($r == "readonly") {$r = "disabled";}
#	Network Choice
   ?>
   <?php
#
#	Change the buttons depending if Edit enabled
#
   if ($readform == "readonly")
   {
   ?>
   <br>
   <input type="hidden" name="menuselect" value=<?php echo $menu_mode ?>>
   <input type="submit" name="submit" value="Edit Values">
   <?php if ($changesmade) echo "<font color='Red'>Please restart your WiPi-Air to effect changes<font color='Black'>"; ?>
   <br><br>
   <input type="submit" name="submit" value="Shutdown WiPi-Power">
   <input type="submit" name="submit" value="Restart WiPi-Power">
   <input type="submit" name="submit" value="Restart WiFi Network">
   <input type="submit" name="submit" value="Restart Power App">
   <?php
   }
   else
   {
#
#	Wi-Fi Add/Remove Buttons
#
     if ($numberofnetworks !== 10)
     {
     ?>
     <br>
     <input type="submit" name="submit" value="Add">
     <?php
     }
     if ($numberofnetworks !== 1)
     {
     ?>
     <input type="submit" name="submit" value="Delete">
     <?php
     }
     ?>

     <br><br>
     <input type="hidden" name="menuselect" value=<?php echo $menu_mode ?>>
     <input type="submit" name="submit" value="Apply Changes">
     <input type="submit" name="submit" value="Reset Values">
     <?php
   }
	$txpower = getTxPower();
   ?>
	</form>
        </p>
	<p>
	<form  method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" autocomplete="off">
	Radio TX Power:            <input type="text" name="txpower"  Value= <?php echo $txpower?> size="2" maxlength="2" pattern="[0-9]+" required title ="Numeric only" onchange="this.form.submit()"> <br>
	<input type="hidden" name="menuselect" value=<?php echo $menu_mode ?>>
	</form>
        </p>
   <p>
   Wi-Fi Status:
   <?php
   exec('lsusb',$lsusb,$iwreturn);
   exec('iwconfig wlan0',$iwoutput,$iwreturn);

   # display the signal strength
   display_signal($iwoutput);
   ?>
   <?php

   switch (count($iwoutput))
   {
   case 0:
     echo "No wireless device available", "<br>";
     foreach ($lsusb as $value) { echo "<div style=\"line-height: 40%\"; ><pre>", $value, "</pre></div>"; }
     break;
   case 5:
     echo "No wireless network attached", "<br>";
     foreach ($lsusb as $value) { echo "<div style=\"line-height: 40%\"; ><pre>", $value, "</pre></div>"; }
     break;
   case 8 || 9:
     echo "<br>";
     foreach ($iwoutput as $value) { echo "<div style=\"line-height: 40%\"; ><pre>", $value, "</pre></div>"; }
     foreach ($lsusb as $value) { echo "<div style=\"line-height: 40%\"; ><pre>", $value, "</pre></div>"; }
     break;
   default:
     echo "Network status unrecognised:", count($iwoutput), "<br>";
   }
   ?>
  </p>

<?php
#
#	Footer section of page
#
?>
   <p>
   <small>Overall &copy IT and Media Services 2020-<?php echo date("y"); ?></small>
  </p>
 </div>
</div>
<!-- s:853e9a42efca88ae0dd1a83aeb215047 -->
</body>
</html>
