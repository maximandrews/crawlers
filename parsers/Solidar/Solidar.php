<?php
require_once(BASE_PATH.'/base_parser.php');

class Solidar extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.solidar.ru/services/fizicheskim_licam/valuta/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<TABLE style','</TABLE>', 0, true);
		$cnt = trim($data['data']);
		$cnt = iconv('windows-1251', 'UTF-8', $cnt);

		//Looping trough all rates rows
		$start =0;
		while(is_int($start) && $row = findData($cnt, '<TR>', '</TR>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<TD>','</TD>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			$symbol = str_replace('Евро', 'EUR', $symbol);
			$symbol = str_replace('Доллары США', 'USD', $symbol);

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<TD>','</TD>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<TD>','</TD>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, 
				0 
			);
		}
	}
}

?>