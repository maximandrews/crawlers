<?php
require_once(BASE_PATH.'/base_parser.php');

class ExpoBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', Array(
			'url' => 'http://expobank.ru/',
			'cookie' => 'BITRIX_SM_GeoIPSelectedCity=%D0%9C%D0%BE%D1%81%D0%BA%D0%B2%D0%B0'
		));
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table class="rates_table">','<a href="/about/currency-exchange/moscow/">', 0, true, false);
		$cnt = trim($data['data']);

		// Remove <thead>
		$cnt = preg_replace('!<thead>.*?</thead>!is', '', $cnt);
		// Allow only <tr>, <td> and <th>
		$cnt = strip_tags($cnt, '<tr><td><th>');
		// Remove all element attributes
		$cnt = preg_replace('/<([\w\d]+)[^>]*>/i', '<\\1>', $cnt);
		// Replace <th> to <td>
		$cnt = str_replace(array('<th>', '</th>'), array('<td>', '</td>'), $cnt);
		// Remove all spaces
		$cnt = preg_replace('/[\s+]/', '', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt  = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = $data['data'];

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate($data['data']);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate($data['data']);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>