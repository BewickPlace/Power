<?php // content="text/plain; charset=utf-8"
#
#	JpGraph Graphing functions
#
require_once ('jpgraph/jpgraph.php');
require_once ('jpgraph/jpgraph_bar.php');
require_once ('jpgraph/jpgraph_line.php');
require_once ('jpgraph/jpgraph_date.php');
require_once ('jpgraph/jpgraph_utils.inc.php');
#
#	Include this as not available within Jesiie PHP and required by JPGraph
#
function imageantialias($image, $enabled){
        return true;
    }
#
#	Simplify Array
#
function simplify_array(&$data) {
    $i;

    for ($i = 0; $i < count($data); $i++) {
	if ($data[$i] > 0) { $data[$i] = 1; }
    }
}

#	Expand Array
#	Expand the source arrays into a single column
#	with timestamps acording to the first array
#
function expand_array($time1, $time2, $source) {
    $i = 1;
    $j = 1;
    $out = array("Header");
    $value = NULL;

    for ($i = 1; $i < count($time1); $i++) {

	if ($j < count($time2)) { $cmp = strcmp($time1[$i], $time2[$j]); }
	while(($cmp >= 0) && ($j < count($time2))) {
	    $value = $source[$j];

	    $j++;
	    if ($j < count($time2)) { $cmp = strcmp($time1[$i], $time2[$j]); }
	}
	$out[] = $value;
    }
    return($out);
}
#
#	Get Monthly Average
#
function get_monthly_avg($node, $year, $month) {
    $avg = 0;
    $count = 0;

    $logfile = '/mnt/storage/Power/'.$node.'_'.$year.'.csv';
    if (file_exists($logfile)) {
	$csv = array_map('str_getcsv', file($logfile));
	$dates = array_column($csv, 0);
	$power = array_column($csv, 1);
	array_shift($dates);
	array_shift($power);

	$i = 0;
	foreach ($power as $value) {
	    $match = strtok($dates[$i], "-");
	    $match = strtok("\n");

	    if($match == $month) {
		$avg = $avg + $value;
		$count++;
	    }
	    $i++;
	}
    }
    $avg = round((($count > 0)? $avg/$count : 0), 3);
    return($avg);
}

#
#	Generate Power Monthly Graph
#
function generate_monthly_power($node, $selected_date) {
    $dates = array();
    $power = array();

    $year = strtok($selected_date, "-");
    $month = strtok("-");

    $time = strtotime($selected_date);
    $base = date("Y-m-01", $time);

    if($month > 6) { $month = $month - 6; $year--; }
    else           { $month = $month + 6; $year--; $year--;}

    for($i=0; $i<=18; $i++) {
	$date = $base." -".(18-$i)." month";
	$time = strtotime($date);

	$dates[] = $time;
	$power[] = get_monthly_avg($node, $year, $month);

	$month = $month + 1;
	if($month > 12) { $month = 1; $year++; }
    }
    $date = $base." + 1 month";
    $time = strtotime($date);
    $dates[] = $time;
    $power[] = 0;

// Now get labels at the start of each month
    $dateUtils = new DateScaleUtils();
    list($tickPositions,$minTickPositions) = $dateUtils->GetTicks($dates);

// We add some grace to the end of the X-axis scale so that the first and last
// data point isn't exactly at the very end or beginning of the scale
    $grace = 000000;
    $xmin = $dates[0]-$grace;
    $xmax = $dates[19]+$grace;

// Create the graph. These two calls are always required
    $graph = new Graph(1600,400);
    $graph->clearTheme();
    $graph->SetScale("datint");
    $graph->yaxis->scale->SetAutoMin(0);

//    $graph->SetClipping(TRUE);

    $graph->SetShadow();
    $graph->img->SetMargin(60,30,20,40);

// Create the bar plots
    $lplot = new LinePlot($power, $dates);
//    $lplot->SetBarCenter();
    $lplot->SetStepStyle();
    $lplot->SetColor("orange");
    $lplot->SetFillColor("orange");

//    $lplot->SetWidth(1.0);

// ...and add it to the graPH
    $graph->Add($lplot);

    $title = "Average Power Usage Daily per Month for 18 months";
    $graph->title->Set($title);
    $graph->xaxis->title->Set("Month");
#    $graph->xaxis->scale->SetDateFormat('M');
#    $graph->xaxis->scale->SetDateAlign(MONTHADJ_1, MONTHADJ_1);
    $graph->yaxis->title->Set("kWh");
    $graph->yaxis->SetTitleMargin(40);

    $graph->xaxis->SetPos('min');

// Now set the tic positions
    $graph->xaxis->SetTickPositions($tickPositions,$minTickPositions);
    $graph->xaxis->SetLabelFormatString('M',true);

    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

    $graph->SetTickDensity(TICKD_NORMAL, TICKD_VERYSPARSE);

#    $graph->xaxis->scale->ticks->SupressFirst(True);
    $graph->xaxis->scale->ticks->SupressLast(True);

// Display the graph to image file
    $filename=$node.'1.png';
    $graph->Stroke($filename);
    return(0);
}

#
#	Generate Power Daily Graph
#
function generate_daily_power($node, $selected_date) {

    $dates = array();
    $hours_run = array();
    $status_ok = array();
    $status_inc = array();
    $status_nod = array();
    $status = 0;
    $base = strtotime("midnight", time());

    for($i = 30; $i > 0 ; $i--) {
#
#	Get the Power Usage for each Day & form array for display
#
	$time = $base - (($i-1)*24*60*60);
	$date = date('Y-m-d', $time);
	$power[30-$i] = get_power_usage($node, $date, $status);

        $dates[]	= $time;
	$status_ok[]	= ( $status==2 ? 1 : 0);
	$status_inc[]	= ( $status==1 ? 1 : 0);
	$status_nod[]	= ( $status==0 ? 1 : 0);
    }
#	Add blank cells
    $dates[] = $base + (24*60*60);
    $power[] = 0;
    $status_ok[] = 0;
    $status_inc[]= 0;
    $status_nod[]= 0;


// Create the graph. These two calls are always required
    $graph = new Graph(1600,400);
    $graph->clearTheme();
    $graph->SetScale("datint");
    $graph->yaxis->scale->SetAutoMin(0);

//    $graph->SetClipping(TRUE);

    $graph->SetShadow();
    $graph->img->SetMargin(60,30,20,40);

// Create the bar plots
    $lplot = new LinePlot($power, $dates);
//    $lplot->SetBarCenter();
    $lplot->SetStepStyle();
    $lplot->SetColor("orange");
    $lplot->SetFillColor("orange");

//    $lplot->SetWidth(1.0);

// Create Controls Line plots
    $lplot2 = new LinePlot($status_ok, $dates);
    $lplot2->SetBarCenter();
    $lplot2->SetStepStyle();
    $lplot2->SetColor("green");
    $lplot2->SetFillColor("green");

    $lplot1 = new LinePlot($status_inc, $dates);
//    $lplot1->SetBarCenter();
    $lplot1->SetStepStyle();
    $lplot1->SetColor("blue");
    $lplot1->SetFillColor("blue");

    $lplot0 = new LinePlot($status_nod, $dates);
//    $lplot0->SetBarCenter();
    $lplot0->SetStepStyle();
    $lplot0->SetColor("red");
    $lplot0->SetFillColor("red");

// ...and add it to the graPH
    $graph->Add($lplot);
    $graph->AddY(0,$lplot2);
    $graph->AddY(1,$lplot1);
    $graph->AddY(2,$lplot0);
//    $graph->Add($lplot);

    $graph->SetYscale(0,'lin', 0, 50);
    $graph->SetYscale(1,'lin', 0, 50);
    $graph->SetYscale(2,'lin', 0, 50);
    $graph->ynaxis[0]->Hide();
    $graph->ynaxis[1]->Hide();
    $graph->ynaxis[2]->Hide();

    $title = "Power Usage per Day for last month";
    $graph->title->Set($title);
    $graph->xaxis->title->Set("Days");
    $graph->xaxis->scale->SetDateFormat('d-M');
    $graph->xaxis->scale->SetDateAlign(DAYADJ_1);
#    $graph->xaxis->SetLabelAngle(90);
    $graph->yaxis->title->Set("kWh");
//    $graph->yaxis->scale->SetDateFormat('H:i');
    $graph->yaxis->SetTitleMargin(40);

    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

    $graph->SetTickDensity(TICKD_NORMAL, TICKD_VERYSPARSE);

//    $graph->xaxis->SetTickLabels($dates);
//    $graph->xaxis->SetTextTickInterval(1440);
//    $graph->xaxis->SetTextLabelInterval(2400);
    $graph->xaxis->scale->ticks->SupressMinorTickMarks(True);
//    $graph->xaxis->scale->ticks->SupressFirst(True);
    $graph->xaxis->scale->ticks->SupressLast(True);

// Display the graph to image file
    $filename=$node.'.png';
    $graph->Stroke($filename);

}

#
#	Gerenrate Todays Power Graph
#
function generate_todays_power($node, $selected_date) {


    $logfile = '/mnt/storage/Power/'.$node.'_'.$selected_date.'.csv';
    if (file_exists($logfile)) {
	$csv = array_map('str_getcsv', file($logfile));
	$dates = array_column($csv, 0);
	$power = array_column($csv, 1);
	array_shift($dates);
	array_shift($power);

// Create the graph. These two calls are always required
    $graph = new Graph(1600,400);
    $graph->clearTheme();
    $graph->SetScale("textlin");
    $graph->yaxis->scale->SetAutoMin(0);

    $graph->SetClipping(TRUE);

    $graph->SetShadow();
    $graph->img->SetMargin(60,30,20,40);

// Create the bar plots
//    $b1plot = new BarPlot($setpoint);
//    $b1plot->SetFillColor("orange");
//    $b2plot = new BarPlot($boost);
//    $b2plot->SetFillColor("red");

// Create the grouped bar plot
//    $gbplot = new AccBarPlot(array($b1plot,$b2plot));
//    $gbplot->SetWidth(1.0);

// Create the Line plot
    $lplot = new LinePlot($power);

//    $lplot->SetBarCenter();
    $lplot->SetColor("blue");
    $lplot->mark->SetType(MARK_UTRIANGLE,'',1.0);
    $lplot->mark->SetWeight(2);
    $lplot->mark->SetWidth(8);
    $lplot->mark->setColor("blue");
    $lplot->mark->setFillColor("blue");

// Create Controls Line plots
//    $lplot2 = new LinePlot($athome);
//    $lplot2->SetBarCenter();
//    $lplot2->SetStepStyle();
//    $lplot2->SetColor("blue");
//    $lplot2->SetFillColor("blue");

//    $lplot3 = new LinePlot($zone);
//    $lplot3->SetBarCenter();
//    $lplot3->SetStepStyle();
//    $lplot3->SetColor("red");
//    $lplot3->SetFillColor("red");

// ...and add it to the graPH
//    $graph->Add($gbplot);
//    $graph->AddY(0,$lplot3);
//    $graph->AddY(1,$lplot2);
    $graph->Add($lplot);

//    $graph->SetYscale(0,'lin', 0, 25);
//    $graph->SetYscale(1,'lin', 0, 50);
//    $graph->ynaxis[0]->Hide();
//    $graph->ynaxis[1]->Hide();

    $title = "Daily Power Usage (".$node. ")";
    $graph->title->Set($title);
    $graph->xaxis->title->Set("Time of Day");
    $graph->yaxis->title->Set("Instantaneous Power kW");
    $graph->yaxis->SetTitleMargin(40);

    $graph->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->yaxis->title->SetFont(FF_FONT1,FS_BOLD);
    $graph->xaxis->title->SetFont(FF_FONT1,FS_BOLD);

    $graph->xaxis->SetTickLabels($dates);
    $graph->xaxis->SetTextTickInterval(12);
    $graph->xaxis->SetTextLabelInterval(1);

// Display the graph to image file
    $filename=$node.'.png';
    $graph->Stroke($filename);



    } else {
        echo "<font color='Red'>".$node.": No data currently accessible (", $logfile, ")<font color='Black'>", "<br><br>";
	$retval = 1;
    }
}
