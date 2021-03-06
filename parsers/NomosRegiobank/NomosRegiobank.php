<?php
require_once(BASE_PATH.'/base_parser.php');

class NomosRegiobank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.nomos-regiobank.ru/currency/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page. 
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table border="0">','<div class="bottom_menu b30">', 0, true);
		$cnt = trim($data['data']);
		
		//Looping trough all rates rows
		$start = strpos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(str_replace('&nbsp;', '', strip_tags($data['data'])));
			$qty = trim(preg_replace("/\D+/is", '', $symbol));
			$symbol = iconv('windows-1251', 'UTF-8', $symbol);
			$symbol = str_replace('Доллар США', 'USD', $symbol);
			$symbol = str_replace('Евро', 'EUR', $symbol);
			$symbol = str_replace('Японских йен', 'JPY', $symbol);
			$symbol = str_replace('Китайских юаней', 'CNY', $symbol);
			$symbol = preg_replace("/[^A-Z]+/is", '', $symbol);
			
			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])), $qty);
			
			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])), $qty);
			
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				$qty, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>