<?php
require_once(BASE_PATH.'/base_parser.php');

class SDMBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.sdm.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'кроме отделения','Для отделения', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<\/tr[^>]+>/is", '</tr>', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Default deal amount
			$qty = 1;

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buy = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sell = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID
				$qty, //Minimal deal amount
				$buy, //Cash foreign currency buy rate
				$sell, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>