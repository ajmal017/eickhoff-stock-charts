<?php

/*
	Get Historical data from Yahoo Finance
	
	in: symbol, start_date, end_date, additional_days_back
	
	out: array of "symbol,date,volumne,high,low,open,close"
*/

function yahoo($symbol, $start_date, $end_date, $additional_days_back) {
	// scrape page for cookie/crumb
	$result = curl(array(
		"url" => "https://finance.yahoo.com/quote/{$symbol}",
		"cookie" => false,
		"header" => true
	));

	$cookie = get_cookie($result);
	$crumb = get_crumb($result);
	
	$start = strtotime("{$start_date} midnight -{$additional_days_back} day" );
	$end = strtotime("{$end_date} midnight +1 day" );

	// get CSV data
	$result = curl(array(
		"url" => "https://query1.finance.yahoo.com/v7/finance/download/{$symbol}?period1={$start}&period2={$end}&interval=1d&events=history&crumb={$crumb}",
		"cookie" => "Cookie: B={$cookie}",
		"header" => false
	));	
	
	// clean end of data
	$data = rtrim ($result, "\n\r");

	// split into array
	$arr_data = explode("\n", $data);
	
	// remove header
	array_shift($arr_data);

	$arr_temp = array();

	foreach ($arr_data as $row) {
		list($d, $o, $h, $l, $c, $a, $v) = explode(",", $row);

		$factor = $a / $c;
		
		$o = $o * $factor;
		$h = $h * $factor;
		$l = $l * $factor;
		$c = $a * $factor;
		$v = $v / $factor;
		
		$arr_temp[] = "{$symbol},{$d},{$v},{$h},{$l},{$o},{$c}";
	} 
	return $arr_temp;
}


function curl($param) {
	$ch = curl_init($param["url"]);

	if ($param["cookie"]) {
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			$param["cookie"]
		));
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	// return headers
	if ($param["header"]) {
		curl_setopt($ch, CURLOPT_HEADER, 1);
	}
	$result = curl_exec($ch);
	curl_close($ch);
	
	return $result;
}

// get cookie
function get_cookie($result) {
	$cookies = array();
	$cookieParts = array();
	preg_match_all('/Set-Cookie:(?<cookie>\s{0,}.*)$/im', $result, $cookies);
	foreach ($cookies[0] as $cookie) {
		preg_match_all('/Set-Cookie:\s{0,}B=(.*?);/im', $cookie, $cookieParts);
	} 
	return $cookieParts[1][0];
}

// get crumb
function get_crumb($result) {
	$crumb = array();
	preg_match('"CrumbStore\":{\"crumb\":\"(?<crumb>.+?)\"}"', $result, $crumb);  // can contain \uXXXX chars
	return json_decode('"' . $crumb['crumb'] . '"');
}


?>