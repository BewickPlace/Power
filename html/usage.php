<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<?php
require 'html_functions.php';
require 'functions.php';
require 'graph.php';
$hostname = getmyhostname();
print("<title>".$hostname.": Usage Profile</title>");
?>
<link rel="stylesheet" type="text/css" href="style.css">
</head>

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
$submenu_mode="";
?>

 <div id="body">

<?php
#
#	WiPi-Power MASTER Display Page
#
#    var_dump($_POST);
#
?>
    <h2>Usage Profile</h2>
    <p>
<?php
    if (!isset($_POST["graph_date"])) {

    $selected_date = html_select_date($menu_mode, $submenu_mode);
    $node = $hostname;
    $filename = $node.'.png';
    echo "<br>";

    if (file_exists($filename)) { unlink($filename);}
#	Generate first graph - Hours Run Daily for the last month
    if (generate_daily_power($node, $selected_date) == 0) {
     ?> <img src=<?php echo $filename ?> height=50% width=100%><?php
    }
?>
    </p>
    <p>
<?php
#	Generate second graph - Hours Run (avg) by month for the year
    $filename = $node.'1.png';
    if (file_exists($filename)) { unlink($filename);}
    if (generate_monthly_power($node, $selected_date) == 0) {
     ?> <img src=<?php echo $filename ?> height=50% width=100%><?php
    }

    } else {

    $selected_date = html_select_date($menu_mode, $submenu_mode);
#
#	Get the Hours Run for specified date
#
	$hours_run = round(get_power_usage($hostname, $selected_date, $status),3);
	echo "<br>Daily total Power usage today: ", $hours_run, "kWh<br>";
?>
    </p>

<?php
	$node = $hostname;
	$filename = $node.'.png';
?>
    <p>
<?php
    if (file_exists($filename)) { unlink($filename);}
    if (generate_todays_power($node, $selected_date) == 0) {
     ?> <img src=<?php echo $filename ?> height=50% width=100%><?php
    }
?>
    </p>

<?php
    }
?>
    <p>
    <small>Overall &copy IT and Media Services 2020-<?php echo date("y"); ?></small>
    </p>
 </div>
</div>
<!-- s:853e9a42efca88ae0dd1a83aeb215047 -->
</body>
</html>
