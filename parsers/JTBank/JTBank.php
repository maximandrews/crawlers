<?php
require_once(BASE_PATH.'/base_parser.php');

class JtBank extends baseParser {

	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://jtbank.ru/fizicheskim-litsam/obmen-valyut');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'Продажа','</tbody>', 0, true);
		$cnt = trim($data['data']);

		//Unifying tags for simplifying search
		$cnt = preg_replace("/<\/?(span|strong)[^>]*>/is", '', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			$rstart = 0;
			$symbol = 'USD';
			for ($z = 1; $z <= 3; $z++) {

				// Setting currency symbol
				if ($z == 2) {
					$symbol = 'EUR';
				} elseif ($z == 3) {
					$symbol = 'CZK';
				}

				//Looking for buying rate (second HTML table cell)
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
				$rstart = $data['end'];

				//Looking for selling rate (third HTML table cell)
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
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
