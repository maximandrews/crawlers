<?php
require_once(BASE_PATH.'/base_parser.php');

class PromServissBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.psb.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div id="currency_for_112">','<div id="currency_for_', 0, true);
		$cnt = trim($data['data']);

		//Unifying and modifying tags for simplifying search
		//$cnt = preg_replace("/<div[^>]+>/is", '<div>', $cnt);
		$cnt = str_replace('euro', 'EUR', $cnt);
		$cnt = str_replace('sale', 'sell', $cnt);
		$cnt = preg_replace("/<div id=\"([a-z]+)_([a-z]+)\">/is", '<tr><div>\\1</div><div>\\2</div>', $cnt);
		$cnt = str_replace('</div></div>', '</div></tr>', $cnt);

		//Array for rates
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			$rstart = 0;
			$data = findData($rcnt, '<div>', '</div>', $rstart, true);
			$symbol = strtoupper(trim(strip_tags($data['data'])));
			$rstart = $data['end'];

			$data = findData($rcnt, '<div>', '</div>', $rstart, true);
			$dealType = trim(strip_tags($data['data']));

			$data = findData($rcnt, '<div id="0-amount">', '</div>', 0, true);
			$qty = trim(strip_tags($data['data']));

			$data = findData($rcnt, '<div id="0">', '</div>', 0, true);
			$rate = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			if(!isset($rates[$symbol])) $rates[$symbol] = Array();
			if(!isset($rates[$symbol][$qty])) $rates[$symbol][$qty] = Array();
			$rates[$symbol][$qty][$dealType] = $rate;
		}

		foreach($rates as $symbol => $qtys) {
			foreach($qtys as $qty => $rate) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
					$this->GetSymbolID($symbol), //Symbol ID.
					$qty, //Minimal deal amount. Mentioned on the page, this value is 1.
					$rate['buy'], //Cash foreign currency buy rate
					$rate['sell'], //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
			}
		}
	}
}

?>