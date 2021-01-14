<?php
require_once(BASE_PATH.'/base_parser.php');

class ORGBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.orgbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<td class="text-center" valign="top">','&nbsp;&nbsp;&nbsp;&nbsp;', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		// Modifying tags for simplifying search
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<div[^>]+>/is", '<div>', $cnt);
		$cnt = str_replace(' - ', '.', $cnt);
		$cnt = str_replace('---', '0.00', $cnt);

		//This array will store all rates
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		$symbol = ''; // Currency symbol
		$count = 1; // Number of block for minimal deal amounts
		while(is_int($start) && $row = findData($cnt, 'ОПЕРУ', 'Мичуринский', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;

			// Setting minimal deal amount
			if ($count == 1) $qty = 1;
			if ($count == 2) $qty = 20000;
			if ($count == 3) $qty = 50000;

			$rstart = 0;
			for ($z = 1; $z <= 3; $z++) {
				// Setting currency symbol
				if ($z == 1) $symbol = 'USD';
				if ($z == 2) $symbol = 'EUR';
				if ($z == 3) $symbol = 'GBP';

				//Looking for buying rate (second HTML table cell)
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$rates[$symbol][$qty]['buy'] = $this->CheckRate(trim(strip_tags($data['data'])));
				$rstart = $data['end'];

				//Looking for selling rate (third HTML table cell)
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$rates[$symbol][$qty]['sell'] = $this->CheckRate(trim(strip_tags($data['data'])));
				$rstart = $data['end'];
			}
			$count++;
		}

		foreach ($rates as $symbol => $qtys) {
			foreach ($qtys as $qty => $rate) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID
					$qty, //Minimal deal amount
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