<?php
require_once(BASE_PATH.'/base_parser.php');

class TCBank extends baseParser {
		
	public function Init() {
		/*	Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.tcbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<TABLE cellSpacing=1 cellPadding=3 width="190" border=0>','</TABLE>', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Looping trough all rates rows
		$start = stripos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//USD: 32.65 / 33.15
			$raterow = trim(strip_tags($rcnt));
			list($symbol, $rate) = explode(':', $raterow);

			$symbol = trim($symbol);
			// 32.65 / 33.15
			list($buyCash, $sellCash) = explode('/', $rate);
			$buyCash = $this->CheckRate(trim($buyCash));
			$sellCash = $this->CheckRate(trim($sellCash));

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