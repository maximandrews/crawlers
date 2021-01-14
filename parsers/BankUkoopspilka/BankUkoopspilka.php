<?php
require_once(BASE_PATH.'/base_parser.php');

class BankUkoopspilka extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bankukoopspilka.kiev.ua/ua/karta_filij');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table','</table>', 0, true);
		$cnt = trim($data['data']);

		// Unifying and modifying tags for simplifying search
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		// Replacing currency names in Ukrainian with symbols
		$cnt = str_replace('Долар США','USD',$cnt);
		$cnt = str_replace('ЕВРО','EUR',$cnt);
		$cnt = str_replace('Російський Рубль','RUB',$cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			// Passing by currency digital symbol
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);

			//Looking for currency symbol (first HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			$qty = $symbol == 'RUB' ? 10:100;

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				$qty, //Minimal deal amount. Mentioned on page
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>