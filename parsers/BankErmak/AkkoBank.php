<?php
require_once(BASE_PATH.'/base_parser.php');

class AkkoBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.akkobank.ru/');
	}
	public function ParseRates($alldata=Array()) {
			/*
			We have only one URL that's why $alldata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($alldata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<td colspan=3>','</table>', 0, true);
		$cnt = trim($data['data']);
		$cnt = str_ireplace('<br/>', '<br />', $cnt);

		//Looping trough all rates rows
		$start = 0;
		if(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0; 
			$data = findData($rcnt, '<p>', '</td>', $rstart, true);
			$symbols = explode("<br />", $data['data']);
			$symbols[0] = trim(strip_tags($symbols[0]));
			$symbols[1] = trim(strip_tags($symbols[1]));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<p>', '</td>', $rstart, true);
			$buyCash = explode("<br />", $data['data']);
			$buyCash[0] = (trim(strip_tags($buyCash[0])));
			$buyCash[1] = (trim(strip_tags($buyCash[1])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end']+2;
			$data = findData($rcnt, '<p>', '</td>',$rstart , true);
			$sellCash = explode("<br />",$data['data']);
			$sellCash[0] = (trim(strip_tags($sellCash[0])));
			$sellCash[1] = (trim(strip_tags($sellCash[1])));

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbols[0]), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash[0],
				//Cash foreign currency buy rate
				$sellCash[0], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate*/
			);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbols[1]), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash[1],
				//Cash foreign currency buy rate
				$sellCash[1], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate*/
			);
		}
	}
}
?>