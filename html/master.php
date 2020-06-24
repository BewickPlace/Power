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
print("<title>".$hostname.": Electricity Monitoring System</title>");
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
?>

 <div id="body" align="center" width="%50">

<?php
#
#	WiPi-Power MASTER Display Page
#
#
?>
    <h2>WiPi-Power</h2>
    <h2>Electricity Monitoring System</h2>

    <div id ="circle-plain" class="circle">
	<?php echo $hostname ?>
    </div>
    <p>
	Welcome to your WiPi-Power. <br><br>
<?php
	$time = time();
	$date = date('Y-m-d',$time);
	$year = strtok($date, "-");
	$month = strtok("-");

	$hours_run = round(get_power_usage($hostname, $date, $status),1);
	echo "Power usage so far today: ", $hours_run, "kWh<br>";

	$hours_run = round(get_monthly_avg($hostname, $year, $month),1);
	echo "Average usage this month: ", $hours_run, "Kwh<br><br>";
?>
    </p>
</div>
<div>
    <p>
    <small>Overall &copy IT and Media Services 2020-<?php echo date("y"); ?></small>
    </p>
</div>
<!-- s:853e9a42efca88ae0dd1a83aeb215047 -->
</body>
</html>
