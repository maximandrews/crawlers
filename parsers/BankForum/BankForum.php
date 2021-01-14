<?php
require_once(BASE_PATH.'/base_parser.php');

class BankForum extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.forum.ua/forumua/ua/mainnavigation/home/home.html');
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

		// Unifying and modifying tags for simplifying search
		$cnt = preg_replace("/<li[^>]+>/is", '<li>', $cnt);
		$cnt = preg_replace("/<\/?(strong)[^>]*>/is", '', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = strtoupper(trim(strip_tags($data['data'])));

			$qty = 1; //Minimal deal amount by default
			// Looking for minimal deal amount
			if(preg_match("/(\d+)([A-Z]{3})/", $symbol, $regs)) {
				$qty = $regs[1];
				$symbol = $regs[2];
			}

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			list($buyCash, $sellCash) = explode('/', trim(strip_tags($data['data'])));
			$buyCash = $this->CheckRate($buyCash, $qty);
			$sellCash = $this->CheckRate($sellCash, $qty);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				$qty, //Minimal deal amount
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0,
				0 
			);
		}
	}
}
?>