<?php
require_once(BASE_PATH.'/base_parser.php');

class AltynBank  extends baseParser {
		
	public function Init() {
		/*	Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.altynbank.com/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<TABLE cellSpacing=2 cellPadding=2 align=left border=0>','</TABLE>', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		$symbol = '';

		//Looping trough all rates rows
		$start = stripos($cnt, '</tr>');
		// $start = stripos($cnt, '</tr>', $start+1); 
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			if(is_int(strpos($rcnt, '129.jpg'))) {
				$symbol = 'EUR';
				continue;
			}elseif(is_int(strpos($rcnt, '131.jpg'))) {
				$symbol = 'USD';
				continue;
			}

			//Looking for buying rate (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

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