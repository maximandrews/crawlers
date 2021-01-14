<?php
require_once(BASE_PATH.'/base_parser.php');

class Mbfi extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.mbfi.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<th class="branch-name">','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Removing unnecessary symbols
		$cnt = str_replace('&nbsp;','',$cnt);
		$cnt = str_replace('&rarr;','',$cnt);
		$cnt = str_replace('&darr;','',$cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];			

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td class="rate-', '">', $rstart, true);
			$symbol = mb_convert_case(trim(strip_tags($data['data'])), MB_CASE_UPPER, 'UTF-8');

			if($symbol == 'SFR') $symbol = 'CHF';

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>','</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt,  '>','</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0,
				0 
			);			
		}
	}
}

?>