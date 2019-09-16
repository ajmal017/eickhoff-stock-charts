<?php

//error_reporting(0);

date_default_timezone_set('America/Chicago');

require_once("../php-include/incl.yahoo.historical.data.php");
require_once("../php-include/incl.ta.eickhoff.php");

$symbol = $_REQUEST['symbol'];
$start = $_REQUEST['start'];
$end = $_REQUEST['end'];

$thickness = ($_REQUEST['thick'] != "") ? $_REQUEST['thick'] : 0;

$additionalDaysBack = 0;
// SMA
if (isset($_REQUEST['sma1']) && $_REQUEST['sma1'] != "") {
	if ($_REQUEST['sma1'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['sma1'];
}
if (isset($_REQUEST['sma2']) && $_REQUEST['sma2'] != "") {
	if ($_REQUEST['sma2'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['sma2'];
}
// EMA
if (isset($_REQUEST['ema1']) && $_REQUEST['ema1'] != "") {
	if ($_REQUEST['ema1'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['ema1'] * 2;
}
if (isset($_REQUEST['ema2']) && $_REQUEST['ema2'] != "") {
	if ($_REQUEST['ema2'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['ema2'] * 2;
}
// Bollinger
if (isset($_REQUEST['bol']) && $_REQUEST['bol'] != "") {
	list($bol_days, $bol_mult) = explode(",", $_REQUEST['bol']);
	if ($bol_days > $additionalDaysBack)
		$additionalDaysBack = $bol_days;
}
// PC
if (isset($_REQUEST['pc']) && $_REQUEST['pc'] != "") {
	if ($_REQUEST['pc'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['pc'];
}
// RSI
if (isset($_REQUEST['rsi']) && $_REQUEST['rsi'] != "") {
	if ($_REQUEST['rsi'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['rsi'];
}
// MACD
if (isset($_REQUEST['macd']) && $_REQUEST['macd'] != "") {
	list($days_fast, $days_slow, $days_smooth) = explode(",", $_REQUEST['macd']);
	if (($days_fast + $days_slow + $days_smooth) > $additionalDaysBack)
		$additionalDaysBack = ($days_fast + $days_slow + $days_smooth);
}
// ADX
if (isset($_REQUEST['adx']) && $_REQUEST['adx'] != "") {
	if ((2 * $_REQUEST['adx']) > $additionalDaysBack)
		$additionalDaysBack = (2 * $_REQUEST['adx']);
}
// MFI
if (isset($_REQUEST['mfi']) && $_REQUEST['mfi'] != "") {
	if ($_REQUEST['mfi'] > $additionalDaysBack)
		$additionalDaysBack = $_REQUEST['mfi'];
}
//echo "$days_fast, $days_slow, $days_smooth; $additionalDaysBack<br/>\n";

$additionalDaysBack = floor($additionalDaysBack * 1.7);

$arr_data = yahoo(array(
	"symbol" => $symbol, 
	"start" => $start, 
	"end" => $end, 
	"offset" => $additionalDaysBack
));

preg_match('/^([0-9]{4})([0-9]{2})([0-9]{2})$/', $start, $matches);
$stActual = strtotime($matches[2] . '/' . $matches[3] . '/' . $matches[1]);


$offset = 0;
foreach ($arr_data as $line) {
	list($s, $d, $v, $h, $l, $o, $c) = explode(",", $line); // other Symbol,Date,Volume,High,Low,Open,Close
	if (strtotime($d) < $stActual) {
		$offset++;
	}
}

$count = count($arr_data);

$lower_plots = 0;
$arr_plots = array();

// SMA
$draw_sma1 = 0;
$draw_sma2 = 0;
$arr_sma1 = array();
$arr_sma2 = array();
if (isset($_REQUEST['sma1']) && $_REQUEST['sma1'] != "") {
	$sma_days1 = $_REQUEST['sma1'];
	$arr_sma1 = SMA ($arr_data, $sma_days1, 6, 2);
	$draw_sma1 = 1;
}
if (isset($_REQUEST['sma2']) && $_REQUEST['sma2'] != "") {
	$sma_days2 = $_REQUEST['sma2'];
	$arr_sma2 = SMA ($arr_data, $sma_days2, 6, 2);
	$draw_sma2 = 1;
}

// EMA
$draw_ema1 = 0;
$draw_ema2 = 0;
$arr_ema1 = array();
$arr_ema2 = array();
if (isset($_REQUEST['ema1']) && $_REQUEST['ema1'] != "") {
	$ema_days1 = $_REQUEST['ema1'];
	$arr_ema1 = EMA ($arr_data, $ema_days1, 6, 2);
	$draw_ema1 = 1;
}
if (isset($_REQUEST['ema2']) && $_REQUEST['ema2'] != "") {
	$ema_days2 = $_REQUEST['ema2'];
	$arr_ema2 = EMA ($arr_data, $ema_days2, 6, 2);
	$draw_ema2 = 1;
}

// Bollinger
$upper = array();
$lower = array();
$middle = array();
$draw_bol = 0;
if (isset($_REQUEST['bol']) && $_REQUEST['bol'] != "") {
	list($bol_days, $bol_mult) = explode(",", $_REQUEST['bol']);

	Bollinger ($arr_data, $bol_days, $bol_mult, 6, 2);
	$draw_bol = 1;
}


// PC
$pc_high = array();
$pc_low = array();
$draw_pc = 0;
if (isset($_REQUEST['pc']) && $_REQUEST['pc'] != "") {
	$pc_days = $_REQUEST['pc'];
	PriceChannels ($arr_data, $pc_days, 3, 4, 2);
	$draw_pc = 1;
}	


// MACD
$macd = array();
$draw_macd = 0;
if (isset($_REQUEST['macd']) && $_REQUEST['macd'] != "") {
	list($days_fast, $days_slow, $days_smooth) = explode(",", $_REQUEST['macd']);
	$macd_ema = array();
	$divergence = array();
	MACD($arr_data, $days_fast, $days_slow, $days_smooth, 6, 3);
	$draw_macd = 1;
	$lower_plots++;
	$arr_plots['macd'] = $lower_plots;
}
	

// Candles
$draw_candle = 0;
$cand_height = 0;
if (isset($_REQUEST['cand']) && $_REQUEST['cand'] = "y") {
	$draw_candle = 1;
	$lower_plots++;
	$arr_plots['cand'] = $lower_plots;
	list ($cand_color, $cand_trend, $cand, $cand_len) = Candles($arr_data, 5, 6, 3, 4);
	$cand_height = 150;
}


// RSI
$rsi = array();
$draw_rsi = 0;	
if (isset($_REQUEST['rsi']) && $_REQUEST['rsi'] != "") {
	$rsi_days = $_REQUEST['rsi'];
	RSI ($arr_data, $rsi_days, 6, 2);
	$maxR = 100;
	$minR = 0;
	$draw_rsi = 1;
	$lower_plots++;
	$arr_plots['rsi'] = $lower_plots;
}
	

// MFI
$mfi = array();
$draw_mfi = 0;
if (isset($_REQUEST['mfi']) && $_REQUEST['mfi'] != "") {
	$mfi_days = $_REQUEST['mfi'];
	MFI ($arr_data, $mfi_days, 3, 4, 6, 2);
	$maxMFI = 100;
	$minMFI = 0;
	$draw_mfi = 1;
	$lower_plots++;
	$arr_plots['mfi'] = $lower_plots;
}
		
	
// ADX
$adx = array();
$draw_adx = 0;
if (isset($_REQUEST['adx']) && $_REQUEST['adx'] != "") {
	$adx_days = $_REQUEST['adx'];
	$DI_plus = array();
	$DI_minus = array();
	ADX ($arr_data, $adx_days, 6, 3, 4, 2);
	$maxA = 100;
	$minA = 0;
	$draw_adx = 1;
	$lower_plots++;
	$arr_plots['adx'] = $lower_plots;
}


// Accumulation/Distribution line
$adl = array();
$draw_adl = 0;
if (isset($_REQUEST['adl']) && $_REQUEST['adl'] == "y") {
	//$s, $d, $v, $h, $l, $o, $c
	// ADL (arr, h, l, c, v)
	ADL ($arr_data, 3, 4, 6, 2);
	$draw_adl = 1;
	$lower_plots++;
	$arr_plots['adl'] = $lower_plots;
}
	


// Vol
$draw_vol = 0;
if (isset($_REQUEST['vol']) && $_REQUEST['vol'] == "y") {
	$draw_vol = 1;
}

// Fib
$draw_fib = 0;
if (isset($_REQUEST['fib']) && $_REQUEST['fib'] == "y") {
	$draw_fib = 1;
}


if ($count > 750 || $count < 2) {
	echo "<br/><br/>Fetched too much or too little data. COUNT: $count<br/><br/>$url\n";
	exit;
}


$col = 12; // space for each candle column
$colCandle 	= 6; // space for each candle
$colVolume 	= 10; // space for each candle
if (isset($_REQUEST['col']) && $_REQUEST['col'] != "") {
	list($col, $colCandle, $colVolume) = explode(",", $_REQUEST['col']);
}

$topMargin 	= 40;
$leftMargin = 40; // offset for beginning of horz grid lines
$leftMarginGraph = 30; // offset for beginning of cand 
$rightMargin = 90; // volume text
if ($draw_vol == 0)
	$rightMargin = 5;

$width = $col * ($count - $offset); // dynamic width
$height = 300;
if (isset($_REQUEST['height'])) {
	if ($_REQUEST['height'] >= 100 && $_REQUEST['height'] <= 1000) {
		$height = $_REQUEST['height'];
	}
}
$lower_height = 100;
if (isset($_REQUEST['lowerheight'])) {
	if ($_REQUEST['lowerheight'] >= 100 && $_REQUEST['lowerheight'] <= 1000) {
		$lower_height = $_REQUEST['lowerheight'];
	}
}

header('Content-type: image/png');

$canvasWidth = $width + $col + $leftMargin + $leftMarginGraph + $rightMargin;

$space_between_upper_lower = 60;
$bottom_height = $lower_height;

$half_bottom_height = floor($bottom_height / 2); // for graphs with respect to centerline (macd divergence)

$im = imagecreatetruecolor($canvasWidth, ($lower_plots * ($space_between_upper_lower + $bottom_height) + $height + 75)); // extra for text at bottom
//$font = 'c:/windows/fonts/arial.ttf';
//$font = '/usr/share/tvtime/tvtimeSansBold.ttf';


// Activate the fast drawing antialiased methods for lines and wired polygons. It does not support alpha components. 
// It works using a direct blend operation. It works only with truecolor images. 
// stopped working on eskimo somewhere around dec 2015 - jan 2016
//imageantialias($im, true);

// Returns a color identifier representing the color composed of the given RGB components.
$white = imageColorAllocate($im, 255, 255, 255);
$white2 = imageColorAllocate($im, 120, 120, 120);
$green = imageColorAllocate($im, 0, 255, 0);
$yellow = imageColorAllocate($im, 255, 255, 0);
$palecanary = imageColorAllocate($im, 255, 255, 200);
$red = imageColorAllocate($im, 255, 0, 0);
$black = imageColorAllocate($im, 0, 0, 0);
$darkgrey = imagecolorallocate($im, 50, 50, 50);
$grey = imagecolorallocate($im, 70, 70, 70);
$lightgrey = imagecolorallocate($im, 100, 100, 100);
$blue = imageColorAllocate($im, 0, 0, 255);
$orange = imageColorAllocate($im, 255, 200, 0);
$lightblue = imageColorAllocate($im, 200, 255, 255);
$lightgreen = imageColorAllocate($im, 200, 255, 200);
$pink = imageColorAllocate($im, 255, 200, 255);
$lightpink = imagecolorallocate($im, 255, 182, 193);
$purple = imageColorAllocate($im, 200, 100, 255);
$darkpurple = imageColorAllocate($im, 100, 50, 127);
$Honeydew3 = imageColorAllocate($im, 193, 205, 193);
$sapgreen = imageColorAllocate($im, 48, 128, 20);
$indianred = imageColorAllocate($im, 176, 23, 31);

imagefilltoborder($im, 0, 0, $black, $black);

// for tracking whether the low comes before high (for fib grid direction)
$offset_low = 0;
$offset_high = 0;

// find min and max for the data
$cnt = 0;
for ($I = $offset; $I < count($arr_data); $I++) {
	$line = $arr_data[$I];
	$cnt++;
	//list($d, $o, $h, $l, $c, $v, $a) = explode(",", $line); // yah
	list($s, $d, $v, $h, $l, $o, $c) = explode(",", $line); // other Symbol,Date,Volume,High,Low,Open,Close
	
	if ($cnt == 1) {
		$min = $l;
		$max = $h;
		$minVol = $v;
		$maxVol = $v;
		$offset_min = $I;
		$offset_max = $I;
	}
	else {
		if ($l < $min) {
			$min = $l;
			$offset_low = $I;
		}
		if ($h > $max) {
			$max = $h;
			$offset_high = $I;
		}
		if ($v < $minVol)
			$minVol = $v;
		if ($v > $maxVol)
			$maxVol = $v;	
	}
}

$fib_direction = "DOWN";
if ($offset_low < $offset_high)
	$fib_direction = "UP";


// adjust min/max for the upper technicals
if (isset($_REQUEST['crop']) && $_REQUEST['crop'] == 'n') {
	// bollinger min max
	for ($I = $offset; $I < count($upper); $I++) {
		if ($lower[$I] < $min)
			$min = $lower[$I];
		if ($upper[$I] > $max)
			$max = $upper[$I];		
	}
	// sma 1
	for ($I = $offset; $I < count($arr_sma1); $I++) {
		if ($arr_sma1[$I] < $min)
			$min = $arr_sma1[$I];
		if ($arr_sma1[$I] > $max)
			$max = $arr_sma1[$I];		
	}
	// sma 2
	for ($I = $offset; $I < count($arr_sma2); $I++) {
		if ($arr_sma2[$I] < $min)
			$min = $arr_sma2[$I];
		if ($arr_sma2[$I] > $max)
			$max = $arr_sma2[$I];		
	}
	// ema 1
	for ($I = $offset; $I < count($arr_ema1); $I++) {
		if ($arr_ema1[$I] < $min)
			$min = $arr_ema1[$I];
		if ($arr_ema1[$I] > $max)
			$max = $arr_ema1[$I];		
	}
	// ema 2
	for ($I = $offset; $I < count($arr_ema2); $I++) {
		if ($arr_ema2[$I] < $min)
			$min = $arr_ema2[$I];
		if ($arr_ema2[$I] > $max)
			$max = $arr_ema2[$I];		
	}
}

// find min and max for the MACD data
$cnt = 0;
$minM = "";
$maxM = "";
for ($I = $offset; $I < count($macd); $I++) {
	$m = $macd[$I];
	$e = $macd_ema[$I];
	$cnt++;

	if ($cnt == 1) {
		$minM = $m;
		if ($e < $minM)
			$minM = $e;
		$maxM = $m;
		if ($e > $maxM)
			$maxM = $m;
	}
	else {
		if ($e < $minM)
			$minM = $e;
		if ($m < $minM)
			$minM = $m;
		if ($m > $maxM)
			$maxM = $m;	
		if ($e > $maxM)
			$maxM = $e;
	}
}

if (abs($minM) > $maxM) {
	$maxM = abs($minM);
	$minM = abs($minM) * -1;
}
else {
	$maxM = abs($maxM);
	$minM = abs($maxM) * -1;
}

//echo "minM = $minM, maxM = $maxM<br/>\n"; exit;

// find ADX min/max
$maxA = 0;
$minA = 100;
for ($I = $offset; $I < count($adx); $I++) {
	if ($adx[$I] < $minA)
		$minA = $adx[$I];
	if ($DI_plus[$I] < $minA)
		$minA = $DI_plus[$I];
	if ($DI_minus[$I] < $minA)
		$minA = $DI_minus[$I];
	if ($adx[$I] > $maxA)
		$maxA = $adx[$I];
	if ($DI_plus[$I] > $maxA)
		$maxA = $DI_plus[$I];
	if ($DI_minus[$I] > $maxA)
		$maxA = $DI_minus[$I];
}

// ADL min max
$ADL_min = isset($adl[$offset]) ? $adl[$offset] : null;
$ADL_max = isset($adl[$offset]) ? $adl[$offset] : null;
for ($I = $offset; $I < count($adl); $I++) {
	if ($adl[$I] < $ADL_min)
		$ADL_min = $adl[$I];
	if ($adl[$I] > $ADL_max)
		$ADL_max = $adl[$I];
}

// find RSI min/max
$maxR = 0;
$minR = 100;
for ($I = $offset; $I < count($rsi); $I++) {
	if ($rsi[$I] < $minR)
		$minR = $rsi[$I];
	if ($rsi[$I] > $maxR)
		$maxR = $rsi[$I];
}


// find MFI min/max
$maxMFI = 0;
$minMFI = 100;
for ($I = $offset; $I < count($mfi); $I++) {
	if ($mfi[$I] < $minMFI)
		$minMFI = $mfi[$I];
	if ($mfi[$I] > $maxMFI)
		$maxMFI = $mfi[$I];
}

if ($draw_fib) {
	if ($fib_direction == "UP")
		$arr_grid = array(1, 0.923, 0.846, 0.764, 0.67, 0.618, 0.5, 0.382, 0.33, 0.236, 0.154, 0.077, 0);
	else
		$arr_grid = array(0, 0.077, 0.154, 0.236, 0.33, 0.382, 0.5, 0.618, 0.67, 0.764, 0.846, 0.923, 1);
}
else if ($height >= 800)
	$arr_grid = array(
		1, 0.975, 0.95, 0.925, 0.9, 0.875, 0.85, 0.825, 0.8, 0.775, 0.75, 0.725, 0.7, 0.675, 0.65, 
		0.625, 0.6, 0.575, 0.55, 0.525, 0.5, 0.475, 0.45, 0.425, 0.4, 0.375, 0.35, 0.325, 0.3, 0.275, 
		0.25, 0.225, 0.2, 0.175, 0.15, 0.125, 0.1, 0.075, 0.05, 0.025, 0
	);
else if ($height >= 400) 
	$arr_grid = array(1, 0.95, 0.9, 0.85, 0.8, 0.75, 0.7, 0.65, 0.6, 0.55, 0.5, 0.45, 0.4, 0.35, 0.3, 0.25, 0.2, 0.15, 0.1, 0.05, 0);
else if ($height >= 300)
	$arr_grid = array(
		1, 0.933333333, 0.866666667, 0.8, 0.733333333, 0.666666667, 0.6, 0.533333333, 
		0.466666667, 0.4, 0.333333333, 0.266666667, 0.2, 0.133333333, 0.066666667, 0
	);
else if ($height >= 200)
	$arr_grid = array(1, 0.9, 0.8, 0.7, 0.6, 0.5, 0.4, 0.3, 0.2, 0.1, 0);
else
	$arr_grid = array(1, 0.88, 0.75, 0.62, 0.5, 0.25, 0.38, 0.12, 0);

// unkown usage
$facVol = 0;

foreach ($arr_grid as $line) {
	$Yline = $height - ($height * $line);
	imageline($im, 36 + $leftMargin, $Yline + $topMargin, $width + $col + $leftMargin + $leftMarginGraph, $Yline + $topMargin, $lightgrey); // 
	$pr = '$' . sprintf('%01.2f',($max - ($max - $min) * (1 - $line)));
	$pr_length = strlen($pr);
	for ($x = 0; $x < (11 - $pr_length); $x++) {
		$pr = ' ' . $pr;
	} 
	imagestring($im, 2, 5, $Yline + $topMargin - 8, $pr, $white); // prc

	if ($draw_vol == 1) {
		$vol = ' ' . number_format($maxVol - ($maxVol - $minVol) * (1 - $line));
		$Y_topVol = sprintf('%d', ($maxVol - $v) * $facVol);
		imagestring($im, 2, $width + $col + $leftMargin + $leftMarginGraph, $Yline + $topMargin - 8, $vol, $white); // vol
	}
}




if ($draw_macd == 1) {
	$arr_grid = array(1, 0.833333333, 0.666666667, 0.5, 0.333333333, 0.166666667, 0);
	foreach ($arr_grid as $line) {
		$Yline2 = ($arr_plots['macd'] * $bottom_height) - ($bottom_height * $line);	
		imageline($im, 36 + $leftMargin, (($arr_plots['macd'] * $space_between_upper_lower) + $height) + $Yline2 + $topMargin, 
			$width + $col + $leftMargin + $leftMarginGraph, 
			(($arr_plots['macd'] * $space_between_upper_lower) + $height) + $Yline2 + $topMargin, $lightgrey); // macd
		$macdAxis = sprintf('%01.3f',($maxM - ($maxM - $minM) * (1 - $line)));
		$macdAxis_length = strlen($macdAxis);
		for ($x = 0; $x < (11 - $macdAxis_length); $x++) {
			$macdAxis = ' ' . $macdAxis;
		} 
		imagestring($im, 2, 5, ($space_between_upper_lower + $height) + $Yline2 + $topMargin - 8, $macdAxis, $white); // macd axis val
		if ($line == 0.5) 
			$zeroM = $space_between_upper_lower + $height + $Yline2 + $topMargin;
	}
}

$textLength = null;

if ($draw_candle) {
	$text = "Candlestick";
	imagestring($im, 2, 20, $topMargin + $height + 40 + (($arr_plots['cand'] - 1) * ($space_between_upper_lower + $bottom_height)), 
		$text, $purple); 
	$textLength += strlen($text);
}

// plots
$cnt = 0;
$dif = $max - $min;
$fac = ($dif != 0) ? $height / $dif : 0;


$difVol = $maxVol - $minVol;
$facVol = ($difVol != 0) ? $height / $difVol : 0;

$difM = $maxM - $minM;
$facM = ($difM != 0) ? $bottom_height / $difM : 0;

$difR = $maxR - $minR;
$facR = ($difR != 0) ? $bottom_height / $difR : 0;

$difMFI = $maxMFI - $minMFI;
$facMFI = ($difMFI != 0) ? $bottom_height / $difMFI : 0;

$difA = $maxA - $minA;
$facA = ($difA != 0) ? $bottom_height / $difA : 0;

$difADL = $ADL_max - $ADL_min;
$facADL = ($difADL != 0) ? $bottom_height / $difADL : 0;

$arr_grid = array(100, 95, 90, 85, 80, 75, 70, 65, 60, 55, 50, 45, 40, 35, 30, 25, 20, 15, 10, 5, 0);
// attempt at smart rsi/adx horizontal lines
if ($draw_adx == 1) {
	foreach ($arr_grid as $line) {
		if ($line <= $maxA && $line >= $minA) {
			$Y = sprintf('%d', ($maxA - $line) * $facA);
			
			$color = $darkgrey;
			if ($line % 10 == 0) {
				$color = $lightgrey;
				$adxAxis = $line;
				$adxAxis_length = strlen($adxAxis);
				for ($x = 0; $x < (11 - $adxAxis_length); $x++) {
					$adxAxis = ' ' . $adxAxis;
				} 
				imagestring($im, 2, 5, (($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y + $topMargin + 
					(($arr_plots['adx'] - 1) * $bottom_height) - 8, $adxAxis, $white); //	axis val
			}
			
			imageline($im, 
				36 + $leftMargin, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height),	
				$width + $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height),
				$color); // adx
				
		}
	}
	$text = "ADX($adx_days)";
	imagestring($im, 2, 20, $topMargin + $height + 40 + (($arr_plots['adx'] - 1) * ($space_between_upper_lower + $bottom_height)), 
		$text, $white); 
	$textLength += strlen($text);
}


$arr_grid2 = array(1, 0.88, 0.75, 0.62, 0.5, 0.25, 0.38, 0.12, 0);
if ($draw_adl == 1) {

	foreach ($arr_grid2 as $line) {
		$Yline2 = ($arr_plots['adl'] * $bottom_height) - ($bottom_height * $line);	
		imageline($im, 36 + $leftMargin, 
			(($arr_plots['adl'] * $space_between_upper_lower) + $height) + $Yline2 + $topMargin, 
			$width + $col + $leftMargin + $leftMarginGraph, 
			(($arr_plots['adl'] * $space_between_upper_lower) + $height) + $Yline2 + $topMargin, $lightgrey); // adl
		$macdAxis = sprintf('%01.0f',($ADL_max - ($ADL_max - $ADL_min) * (1 - $line)));
		$macdAxis = sizetotext($macdAxis);
		$macdAxis_length = strlen($macdAxis);
		for ($x = 0; $x < (11 - $macdAxis_length); $x++) {
			$macdAxis = ' ' . $macdAxis;
		} 

		imagestring($im, 2, 5, (($arr_plots['adl'] * $space_between_upper_lower) + $height) + $Yline2 + $topMargin - 7, 
			$macdAxis, $white); // adl axis val
	}
	
	$text = "Accum/Dist";
	imagestring($im, 2, 20, $topMargin + $height + 40 + (($arr_plots['adl'] - 1) * ($space_between_upper_lower + $bottom_height)), 
		$text, $orange); 
	$textLength += strlen($text);
}


if ($draw_rsi == 1) {
	foreach ($arr_grid as $line) {
		if ($line <= $maxR && $line >= $minR) {
			$Y = sprintf('%d', ($maxR - $line) * $facR);
			
			$color = $darkgrey;
			if ($line % 10 == 0) {
				$color = $lightgrey;
				$adxAxis = $line;
				$adxAxis_length = strlen($adxAxis);
				for ($x = 0; $x < (11 - $adxAxis_length); $x++) {
					$adxAxis = ' ' . $adxAxis;
				} 
				imagestring($im, 2, 5, (($arr_plots['rsi'] * $space_between_upper_lower) + $height) + $Y + $topMargin + 
					(($arr_plots['rsi'] - 1) * $bottom_height) - 8, $adxAxis, $white); //	axis val
			}
			imageline($im, 
				36 + $leftMargin, 
				(($arr_plots['rsi'] * $space_between_upper_lower) + $height) + $Y + $topMargin + (($arr_plots['rsi'] - 1) * $bottom_height),	
				$width + $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['rsi'] * $space_between_upper_lower) + $height) + $Y + $topMargin + (($arr_plots['rsi'] - 1) * $bottom_height),
				$color); // rsi	
				
		}
	}
	$text = "RSI($rsi_days)";
	imagestring($im, 2, 20, $topMargin + $height + 40 + (($arr_plots['rsi'] - 1) * ($space_between_upper_lower + $bottom_height)), 
		$text, $pink); 
	$textLength += strlen($text);
}


if ($draw_mfi == 1) {
	foreach ($arr_grid as $line) {
		if ($line <= $maxMFI && $line >= $minMFI) {
			$Y = sprintf('%d', ($maxMFI - $line) * $facMFI);
			
			$color = $darkgrey;
			if ($line % 10 == 0) {
				$color = $lightgrey;
				$adxAxis = $line;
				$adxAxis_length = strlen($adxAxis);
				for ($x = 0; $x < (11 - $adxAxis_length); $x++) {
					$adxAxis = ' ' . $adxAxis;
				} 
				imagestring($im, 2, 5, (($arr_plots['mfi'] * $space_between_upper_lower) + $height) + $Y + $topMargin + 
					(($arr_plots['mfi'] - 1) * $bottom_height) - 8, $adxAxis, $white); //	axis val
			}
			imageline($im, 
				36 + $leftMargin, 
				(($arr_plots['mfi'] * $space_between_upper_lower) + $height) + $Y + $topMargin + (($arr_plots['mfi'] - 1) * $bottom_height),	
				$width + $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['mfi'] * $space_between_upper_lower) + $height) + $Y + $topMargin + (($arr_plots['mfi'] - 1) * $bottom_height),
				$color); // mfi
				
		}
	}
	$text = "MFI($mfi_days)";
	imagestring($im, 2, 20, $topMargin + $height + 40 + (($arr_plots['mfi'] - 1) * ($space_between_upper_lower + $bottom_height)), 
		$text, $lightgreen); 
	$textLength += strlen($text);
}


$prev_mon = null;
$prev_year = null;
$top_margin = null;

$test_point = count($arr_data) - 5;

$candle_box = array();

$maxTop = $max * 1.01; // defines area to stop drawing upper graph technical lines (sma/ema/bol)
$minBottom = $min * 0.99;
$cnt = 0;
for ($I = $offset; $I < count($arr_data); $I++) {
	$line = $arr_data[$I];

	list($s, $d, $v, $h, $l, $o, $c) = explode(",", $line); // other Symbol,Date,Volume,High,Low,Open,Close

	$cnt++;
	$xCent = $cnt * $col;

	if ($o < $l)
		$l = $o;
	if ($c < $l)
		$l = $c;
	if ($o > $h)
		$h = $o;
	if ($c > $h)
		$h = $c;

	$unix = strtotime($d);
	$mon = date('M', $unix);
	$day = date('j', $unix);
	$woy = date('W', $unix); //week of year
	$year = date('Y', $unix);
	
	// used later to suppress first month label
	$unixNextDay = strtotime($d . " +1 day");
	$monNextDay = date('M', $unixNextDay);
	$yearNextDay = date('Y', $unixNextDay);

	// vol
	if ($draw_vol == 1) {
		$Y_topVol = sprintf('%d', ($maxVol - $v) * $facVol);
		imagefilledrectangle($im, $xCent - ($colVolume / 2) + $leftMargin + $leftMarginGraph, $Y_topVol + $topMargin, $xCent + 
			($colVolume / 2) + $leftMargin + $leftMarginGraph, $height + $topMargin, $darkgrey); // vol
	}
	
	if ($cnt == 1)
		$prev_woy = date('W', ($unix - 86400)); //week of year

	// vertical bars for first trading day of week
	if ($woy != $prev_woy) {
		imageline($im, $xCent + $leftMargin + $leftMarginGraph, $topMargin, $xCent + $leftMargin + $leftMarginGraph, $height + 
			$topMargin, $lightgrey);	// first trading day of week
		// for the lower graphs
		foreach ($arr_plots as $plot) {
			imageline($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				($space_between_upper_lower + $height) + $topMargin + ($plot - 1) * ($space_between_upper_lower + $bottom_height), 
				$xCent + $leftMargin + $leftMarginGraph, 
				($space_between_upper_lower + $height) + $topMargin + $bottom_height + ($plot - 1) * ($space_between_upper_lower + 
				$bottom_height), 
				$lightgrey);	// first trading day of week
			}
	}
		
	$top = $c;
	$bot = $o;
	$color = $green;
	if ($o > $c) {
		$top = $o;
		$bot = $c;
		$color = $red;
	}

	$Y_top = sprintf('%d', ($max - $top) * $fac);
	$Y_bot = sprintf('%d', ($max - $bot) * $fac);
	
	// rectangle requires height of 2 pix
	if ($Y_top == $Y_bot)
		$Y_bot++;
	
	imagefilledrectangle($im, $xCent - sprintf('%d', ($colCandle / 2)) + $leftMargin + $leftMarginGraph, $Y_top + $topMargin, $xCent + 
		sprintf('%d', ($colCandle / 2)) + $leftMargin + $leftMarginGraph, $Y_bot + $topMargin, $color); // rec w/color
	
	$Y_upp = sprintf('%d', ($max - $h) * $fac);
	$Y_low = sprintf('%d', ($max - $l) * $fac);
	
	imageline($im, $xCent + $leftMargin + $leftMarginGraph, $Y_upp + $topMargin, $xCent + $leftMargin + $leftMarginGraph, $Y_top + 
		$topMargin, $white); // upper
	imageline($im, $xCent + $leftMargin + $leftMarginGraph, 1 + $Y_bot + $topMargin, $xCent + $leftMargin + $leftMarginGraph, 1 + 
		$Y_low + $topMargin, $white);	// lower
		
	$candle_box[$I]['x1'] = -3 + $xCent - sprintf('%d', ($colCandle / 2)) + $leftMargin + $leftMarginGraph;
	$candle_box[$I]['y1'] = -3 + $Y_upp + $topMargin;
	$candle_box[$I]['x2'] = 2 + $xCent + sprintf('%d', ($colCandle / 2)) + $leftMargin + $leftMarginGraph;
	$candle_box[$I]['y2'] = 3 + $Y_low + $topMargin;
		
	
	// macd
	if ($draw_macd == 1) {
		$Y_2 = sprintf('%d', ($divergence[$I - 1]) * $facM);
		$Y_1 = sprintf('%d', ($divergence[$I]) * $facM);
		if ($divergence[$I] <= 0 ) {
			imagefilledrectangle($im, 
				$xCent - ($colVolume / 2) + $leftMargin + $leftMarginGraph, 
				$zeroM,
				$xCent + ($colVolume / 2) + $leftMargin + $leftMarginGraph, 
				($space_between_upper_lower + $height + $half_bottom_height) - $Y_1 + $topMargin, 
				$lightpink); // histogram < 0
		}
		else {
			imagefilledrectangle($im, 
				$xCent - ($colVolume / 2) + $leftMargin + $leftMarginGraph, 
				($space_between_upper_lower + $height + $half_bottom_height) - $Y_1 + $topMargin, 
				$xCent + ($colVolume / 2) + $leftMargin + $leftMarginGraph, 
				$zeroM, 
				$lightgreen); // histogram > 0
		}
		if ($cnt >= 2) {
			$Y_2 = sprintf('%d', ($maxM - $macd[$I - 1]) * $facM);
			$Y_1 = sprintf('%d', ($maxM - $macd[$I]) * $facM);
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, ($space_between_upper_lower + $height) + $Y_1 + $topMargin, 
				$xCent - $col + $leftMargin + $leftMarginGraph, ($space_between_upper_lower + $height) + $Y_2 + $topMargin, $green, $thickness);	// macd
			$Y_2 = sprintf('%d', ($maxM - $macd_ema[$I - 1]) * $facM);
			$Y_1 = sprintf('%d', ($maxM - $macd_ema[$I]) * $facM);
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, ($space_between_upper_lower + $height) + $Y_1 + $topMargin, 
				$xCent - $col + $leftMargin + $leftMarginGraph, ($space_between_upper_lower + $height) + $Y_2 + $topMargin, $red, $thickness);	// macd_ema
		}
	}
	
	// rsi
	if ($draw_rsi == 1) {
		if ($cnt >= 2) {
			$Y_2 = sprintf('%d', ($maxR - $rsi[$I - 1]) * $facR);
			$Y_1 = sprintf('%d', ($maxR - $rsi[$I]) * $facR);
			imageBoldLine($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				(($arr_plots['rsi'] * $space_between_upper_lower) + $height) + $Y_1 + $topMargin + (($arr_plots['rsi'] - 1) * $bottom_height), 
				$xCent - $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['rsi'] * $space_between_upper_lower) + $height) + $Y_2 + $topMargin + (($arr_plots['rsi'] - 1) * $bottom_height), 
				$pink, $thickness);	// rsi
		}
	}
	
	// Candle
	if ($draw_candle == 1) {
		if ($cnt >= 2) {
			
			$color = $red;
			if (strpos($cand[$I], '+') !== false && strpos($cand[$I], '-') !== false)
				$color = $orange;
			elseif (strpos($cand[$I], '+') !== false)
				$color = $green;
			
			$size = 2;
			$shift = 7;
			if (strlen($cand[$I]) > 27) {
				$size = 1;
				$shift = 4;
			}
			elseif (strlen($cand[$I]) < 22) {
				$size = 4;
				$shift = 8;
			}
			
			imagestringup(
				$im, 
				$size, 
				$xCent + $leftMargin + $leftMarginGraph - $shift, 
				(($arr_plots['cand'] * $space_between_upper_lower) + $height) + $lower_height + $topMargin + (($arr_plots['cand'] - 1) * $bottom_height),  
				$cand[$I], 
				$color
			);
		}
	}	

	// mfi
	if ($draw_mfi == 1) {
		if ($cnt >= 2) {
			$Y_2 = sprintf('%d', ($maxMFI - $mfi[$I - 1]) * $facMFI);
			$Y_1 = sprintf('%d', ($maxMFI - $mfi[$I]) * $facMFI);
			imageBoldLine($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				(($arr_plots['mfi'] * $space_between_upper_lower) + $height) + $Y_1 + $topMargin + (($arr_plots['mfi'] - 1) * $bottom_height), 
				$xCent - $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['mfi'] * $space_between_upper_lower) + $height) + $Y_2 + $topMargin + (($arr_plots['mfi'] - 1) * $bottom_height), 
				$lightgreen, $thickness);	// mfi			
		}
	}
	
	// adx
	if ($draw_adx == 1) {
		if ($cnt >= 2) {
			$Y_2 = sprintf('%d', ($maxA - $DI_plus[$I - 1]) * $facA);
			$Y_1 = sprintf('%d', ($maxA - $DI_plus[$I]) * $facA);
			imageBoldLine($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y_1 + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height), 
				$xCent - $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y_2 + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height), 
				$sapgreen, $thickness);	// di+
			$Y_2 = sprintf('%d', ($maxA - $DI_minus[$I - 1]) * $facA);
			$Y_1 = sprintf('%d', ($maxA - $DI_minus[$I]) * $facA);
			imageBoldLine($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y_1 + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height), 
				$xCent - $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y_2 + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height), 
				$indianred, $thickness);	// di-
			$Y_2 = sprintf('%d', ($maxA - $adx[$I - 1]) * $facA);
			$Y_1 = sprintf('%d', ($maxA - $adx[$I]) * $facA);
			imageBoldLine($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y_1 + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height), 
				$xCent - $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adx'] * $space_between_upper_lower) + $height) + $Y_2 + $topMargin + (($arr_plots['adx'] - 1) * $bottom_height), 
				$white, $thickness);	// adx
		}
	}
	
	// adl
	if ($draw_adl == 1) {
		if ($cnt >= 2) {
			$Y_2 = sprintf('%d', ($ADL_max - $adl[$I - 1]) * $facADL);
			$Y_1 = sprintf('%d', ($ADL_max - $adl[$I]) * $facADL);
			imageBoldLine($im, 
				$xCent + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adl'] * $space_between_upper_lower) + $height) + $Y_1 + $topMargin + (($arr_plots['adl'] - 1) * $bottom_height), 
				$xCent - $col + $leftMargin + $leftMarginGraph, 
				(($arr_plots['adl'] * $space_between_upper_lower) + $height) + $Y_2 + $topMargin + (($arr_plots['adl'] - 1) * $bottom_height), 
				$orange, $thickness);	// adl
		}
	}

	// sma
	if ($draw_sma1 == 1 && $cnt >= 2) {
		$Y_2 = sprintf('%d', ($max - $arr_sma1[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $arr_sma1[$I]) * $fac);
		if (($arr_sma1[$I] <= $maxTop && $arr_sma1[$I] >= $minBottom) || ($arr_sma1[$I - 1] <= $maxTop && $arr_sma1[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
				$Y_2 + $topMargin, $lightblue, $thickness);	// sma	
	}
	
	if ($draw_sma2 == 1 && $cnt >= 2) {
		$Y_2 = sprintf('%d', ($max - $arr_sma2[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $arr_sma2[$I]) * $fac);
		if (($arr_sma2[$I] <= $maxTop && $arr_sma2[$I] >= $minBottom) || ($arr_sma2[$I - 1] <= $maxTop && $arr_sma2[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
				$Y_2 + $topMargin, $lightgreen, $thickness);	// sma
	}

	// ema
	if ($draw_ema1 == 1 && $cnt >= 2) {
		$Y_2 = sprintf('%d', ($max - $arr_ema1[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $arr_ema1[$I]) * $fac);
		if (($arr_ema1[$I] <= $maxTop && $arr_ema1[$I] >= $minBottom) || ($arr_ema1[$I - 1] <= $maxTop && $arr_ema1[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
				$Y_2 + $topMargin, $sapgreen, $thickness);	// ema
	}
	if ($draw_ema2 == 1 && $cnt >= 2) {
		$Y_2 = sprintf('%d', ($max - $arr_ema2[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $arr_ema2[$I]) * $fac);
		if (($arr_ema2[$I] <= $maxTop && $arr_ema2[$I] >= $minBottom) || ($arr_ema2[$I - 1] <= $maxTop && $arr_ema2[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
				$Y_2 + $topMargin, $indianred, $thickness);	// ema
	}

	// bollinger
	if ($draw_bol == 1 && $cnt >= 2) {
		$Y_2 = sprintf('%d', ($max - $upper[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $upper[$I]) * $fac);
		if (($upper[$I] <= $maxTop && $upper[$I] >= $minBottom) || ($upper[$I - 1] <= $maxTop && $upper[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, $Y_2 + 
				$topMargin, $orange, $thickness);	// upper
		$Y_2 = sprintf('%d', ($max - $lower[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $lower[$I]) * $fac);
		if (($lower[$I] <= $maxTop && $lower[$I] >= $minBottom) || ($lower[$I - 1] <= $maxTop && $lower[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
				$Y_2 + $topMargin, $orange, $thickness);	// lower
		$Y_2 = sprintf('%d', ($max - $middle[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $middle[$I]) * $fac);
		if (($middle[$I] <= $maxTop && $middle[$I] >= $minBottom) || ($middle[$I - 1] <= $maxTop && $middle[$I - 1] >= $minBottom))
			imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
				$Y_2 + $topMargin, $orange, $thickness);	// middle
	}

	// price channels
	if ($draw_pc == 1 && $cnt >= 2) {
		$Y_2 = sprintf('%d', ($max - $pc_high[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $pc_high[$I]) * $fac);
		imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
			$Y_2 + $topMargin, $blue, $thickness);	// upper
		$Y_2 = sprintf('%d', ($max - $pc_low[$I - 1]) * $fac);
		$Y_1 = sprintf('%d', ($max - $pc_low[$I]) * $fac);
		imageBoldLine($im, $xCent + $leftMargin + $leftMarginGraph, $Y_1 + $topMargin, $xCent - $col + $leftMargin + $leftMarginGraph, 
			$Y_2 + $topMargin, $blue, $thickness);	// lower
	}

	// Day, month, year labels
	// offsets keep labels centered on vertical lines
	$offset1 = 4 * (strlen($day) / 2);
	$offset2 = ((strlen($mon) + 1) / 2) * 3;
	$offset3 = ((strlen($year) + 1) / 2) * 3;

	if ($col >= 12 || $woy != $prev_woy) 
		imagestring($im, 1, $xCent + $leftMargin + $leftMarginGraph - $offset1, 20 + $top_margin + $height + 26, $day, $white); // day
	
	if (! ($cnt == 1 && $monNextDay != $mon)) {
		if ($mon != $prev_mon)
			imagestring($im, 1, $xCent + $leftMargin + $leftMarginGraph - $offset2, 20 + $top_margin + $height + 36, $mon, $white); // month
	}
	if (! ($cnt == 1 && $yearNextDay != $year)) {
		if ($year != $prev_year)
			imagestring($im, 1, $xCent + $leftMargin + $leftMarginGraph - $offset3 , 20 + $top_margin + $height + 46, $year, $white); // year
	}
	
	for ($q = 1; $q <= count($arr_plots); $q++) {
	
		if ($col >= 12 || $woy != $prev_woy) 
			imagestring($im, 1, $xCent + $leftMargin + $leftMarginGraph - $offset1, 
				20 + $top_margin + ($q * ($bottom_height + $space_between_upper_lower)) + $height + 26, $day, $white); // day
		
		if (! ($cnt == 1 && $monNextDay != $mon)) {
			if ($mon != $prev_mon)
				imagestring($im, 1, $xCent + $leftMargin + $leftMarginGraph - $offset2, 
					20 + $top_margin + ($q * ($bottom_height + $space_between_upper_lower)) + $height + 36, $mon, $white); // month		}
		}
		if (! ($cnt == 1 && $yearNextDay != $year)) {
			if ($year != $prev_year)
				imagestring($im, 1, $xCent + $leftMargin + $leftMarginGraph - $offset3 , 
					20 + $top_margin + ($q * ($bottom_height + $space_between_upper_lower)) + $height + 46, $year, $white); // year
		}
	}

	$prev_mon = $mon;
	$prev_year = $year;
	$prev_woy = $woy;
}


// draw fib overlay
if ($draw_fib) {

	$purp1 = imageColorAllocate($im, 207, 165, 204);
	//$purp2 = imageColorAllocate($im, 191, 136, 187);
	$purp2 = imageColorAllocate($im, 178, 111, 174);
	$purp3 = imageColorAllocate($im, 167, 88, 162);
	$purp4 = imageColorAllocate($im, 148, 62, 143);
	$purp5 = imageColorAllocate($im, 137, 41, 133);
	$purp6 = imageColorAllocate($im, 116, 1, 113);
	$purp7 = imageColorAllocate($im, 86, 0, 80);

	// fib grid - http://www.investopedia.com/ask/answers/05/fibonacciretracement.asp
	
	/*
		1/13 = 0.077
		2/13 = 0.154
		13/55 = 0.236
		1/3 = 0.333
		13/34 = 0.382
		1/2 = 0.5
		2/3 = 0.666
	*/
	if ($fib_direction == "UP")
		$fib_grid = array(1, 0.923, 0.846, 0.764, 0.67, 0.618, 0.5, 0.382, 0.33, 0.236, 0.154, 0.077, 0);
	else
		$fib_grid = array(0, 0.077, 0.154, 0.236, 0.33, 0.382, 0.5, 0.618, 0.67, 0.764, 0.846, 0.923, 1);
	
	
	//$fib_grid = array(1, 0.923, 0.846, 0.764, 0.667, 0.618, 0.5, 0.382, 0.333, 0.236, 0.154, 0.077, 0);
	$fib_grid_color = array($purp1, $purp6, $purp5, $purp4, $purp3, $purp1, $purp2, $purp1, $purp3, $purp4, $purp5, $purp6, $purp1);
	
	// Fib Grid
	$color = 0;
	foreach ($fib_grid as $line) {
		
		if ($fib_direction == "UP")
			$Yline = $height - ($height * (1 - $line));
		else
			$Yline = $height - ($height * $line);
		
			$pct = sprintf('%01.1f',($line * 100)) . '%';
			$pct_length = strlen($pct);
			for ($x = 0; $x < (6 - $pct_length); $x++) {
				$pct = ' ' . $pct;
			} 
		
		imageBoldLine(
			$im, 36 + $leftMargin, $Yline + $topMargin, $width + $col + $leftMargin + $leftMarginGraph, $Yline + $topMargin, $fib_grid_color[$color], 
			$thickness, $func = 'imageLine'
		);
		
		imagestring($im, 2, 78, $Yline + $topMargin - 13, $pct, $fib_grid_color[$color]); // fib %
		
		$color++;
	}
}

// Candle pattern boxes (drawn on top of all)
if ($draw_candle == 1) {
	$cnt = 0;
	for ($I = $offset; $I < count($arr_data); $I++) {
		if ($cnt++ >= 1 && $draw_candle == 1 && $cand[$I] != "")
			draw_candle_box($im, $I, $cand_len[$I]);	
	}
}

$text = $symbol;
//if ($arr_today['time'] != "") 	$text .= " (" . $arr_today['time'] . ")";
imagestring($im, 5, $canvasWidth - (9 * strlen($text)) - $rightMargin, 6, strtoupper($text), $white); //symbol
imagestring($im, 2, $canvasWidth - (6 * strlen("(c) SCE 2010-" . date('Y'))) - $rightMargin, 24, "(c) SCE 2010-" . date('Y'), $lightgrey); 

$textLength = 0;
if ($draw_pc == 1) {
	$text = "PC($pc_days)";
	imagestring($im, 2, 20 + (7 * $textLength), 10, $text, $blue); 
	$textLength += strlen($text);
}
if ($draw_bol == 1) {
	$text = "BB($bol_days,$bol_mult)";
	imagestring($im, 2, 20 + (7 * $textLength), 10, $text, $orange); 
	$textLength += strlen($text);
}
if ($draw_sma1 == 1) {
	$text = "SMA($sma_days1)";
	imagestring($im, 2, 20 + (7 * $textLength), 10, $text, $lightblue); 
	$textLength += strlen($text);
}
if ($draw_sma2 == 1) {
	$text = "SMA($sma_days2)";
	imagestring($im, 2, 20 + (7 * $textLength), 10, $text, $lightgreen); 
	$textLength += strlen($text);
}
if ($draw_ema1 == 1) {
	$text = "EMA($ema_days1)";
	imagestring($im, 2, 20 + (7 * $textLength), 10, $text, $sapgreen); 
	$textLength += strlen($text);
}
if ($draw_ema2 == 1) {
	$text = "EMA($ema_days2)";
	imagestring($im, 2, 20 + (7 * $textLength), 10, $text, $indianred); 
	$textLength += strlen($text);
}
if ($draw_macd == 1) {
	$text = "MACD($days_fast,$days_slow,$days_smooth)";
	imagestring($im, 2, 20, $topMargin + $height + 38, $text, $red); 
}




imagepng($im);
//imagejpeg($im);
imagedestroy($im);
exit;


function draw_candle_box($im, $index, $pattern_length) {
	global $cand;
	global $candle_box;
	global $palecanary;
	global $red;
	global $orange;
	global $green;
	global $thickness;

	$x1 = $candle_box[$index]['x1'];
	$y1 = $candle_box[$index]['y1'];
	$x2 = $candle_box[$index]['x2'];
	$y2 = $candle_box[$index]['y2'];
	
	for ($i = $index - $pattern_length + 1; $i < $index; $i++) {
		if (isset($candle_box[$i]['x1']) && $candle_box[$i]['x1'] < $x1)
			$x1 = $candle_box[$i]['x1'];
		if (isset($candle_box[$i]['y1']) && $candle_box[$i]['y1'] < $y1)
			$y1 = $candle_box[$i]['y1'];
		if (isset($candle_box[$i]['x2']) && $candle_box[$i]['x2'] > $x2)
			$x2 = $candle_box[$i]['x2'];
		if (isset($candle_box[$i]['y2']) && $candle_box[$i]['y2'] > $y2)
			$y2 = $candle_box[$i]['y2'];
	}
	
	$color = $red;
	if (strpos($cand[$index], '+') !== false && strpos($cand[$index], '-') !== false)
		$color = $orange;
	elseif (strpos($cand[$index], '+') !== false)
		$color = $green;
	
	// top
	//imageBoldLine($im, $x1,	$y1, $x2, $y1, $color, $thickness);
	imageline($im, $x1,	$y1, $x2, $y1, $color);
	// bottom
	//imageBoldLine($im, $x1, $y2, $x2, $y2, $color, $thickness);	
	imageline($im, $x1, $y2, $x2, $y2, $color);
	// left
	//imageBoldLine($im, $x1, $y1, $x1, $y2, $color, $thickness);
	imageline($im, $x1, $y1, $x1, $y2, $color);
	// right
	//imageBoldLine($im, $x2, $y1, $x2, $y2, $color, $thickness);
	imageline($im, $x2, $y1, $x2, $y2, $color);
}



function sizetotext($SIZE) {

	if (abs($SIZE) >= 1000000000){
		$SIZE = $SIZE / 1000000000;
		return sprintf('%01.2f', $SIZE) . " B";
	}
	else if (abs($SIZE) >= 1000000) {
		$SIZE = $SIZE / 1000000;
		return sprintf('%01.2f', $SIZE) . " M";
	}
	else {
		$SIZE = $SIZE / 1000;
		return sprintf('%01.2f', $SIZE) . " K";
	}
}



function imageBoldLine($resource, $x1, $y1, $x2, $y2, $Color, $BoldNess = 2, $func = 'imageLine') { 
	$center = round($BoldNess / 2);

	for ($i = 0; $i <= $BoldNess; $i++) {
		$a = $center - $i; 
		if ($a < 0) {
			$a -= $a;
		} 
	
		for ($j = 0; $j <= $BoldNess; $j++) { 
			$b = $center-$j; 
			if ($b < 0) {
				$b -= $b;
			} 
			$c = sqrt($a * $a + $b * $b); 
			if($c <= $BoldNess) { 
				$func($resource, $x1 +$i, $y1+$j, $x2 +$i, $y2+$j, $Color); 
			} 
		} 
	}         
} 


?>
