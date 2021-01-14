<?php
require_once(BASE_PATH.'/base_parser.php');

class MFBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.mfbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table>','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Removing unnecessary symbols
		$cnt = str_replace('/RUR','',$cnt);
		$cnt = str_replace('р.',',',$cnt);
		$cnt = str_replace('&nbsp;','',$cnt);
		$cnt = str_replace('к.','</td>',$cnt);
		$cnt = str_replace('*','',$cnt);

		//Looping trough all rates rows
		$start = 0;

		//Setting currency type for minimal deal amount
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			// Setting minimal deal amount
			if ($symbol == 'JPY')
				$amount = 100;
			elseif ($symbol == 'CNY')
				$amount = 10;
			else
				$amount = 1;

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '>','</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])), $amount);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt,  '>','</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])), $amount);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
			$this->GetSymbolID($symbol), //Symbol ID.
				$amount, //Minimal deal amount
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0,
				0 
			);			
		}
	}
}

?>