<?php
require_once(BASE_PATH.'/base_parser.php');

class MetroBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.metrobank.ru/js/rates.js');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'var MB_COIN_0000003_SELL_C','var MB_USDxEUR_BUY_C_D', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags> and replacing some tags for simplifying view
		$cnt = str_replace('_BUY_C = ', '<td>', $cnt);
		$cnt = str_replace('_SELL_C = ', '<td>', $cnt);
		$cnt = str_replace('var MB_EURxUSD<td>', '', $cnt);
		$cnt = str_replace('EURxUSD<td>', '', $cnt);
		
		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, 'SELL_C_D', 'USDxEUR', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;
			for ($z = 1; $z <= 5; $z++) {

				// Setting currency symbol
				if ($z == 1) $symbol = 'EUR';
				if ($z == 2) $symbol = 'KZT';
				if ($z == 3) $symbol = 'MDL';
				if ($z == 4) $symbol = 'UAH';
				if ($z == 5) $symbol = 'USD';

				//Looking for buying rate (second HTML table cell)
				$data = findData($rcnt, 'd>', ';', $rstart, true);
				$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
				$rstart = $data['end'];

				//Looking for selling rate (third HTML table cell)
				$data = findData($rcnt, '<td>', ';', $rstart, true);
				$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
				$rstart = $data['end'];

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
}

?>
