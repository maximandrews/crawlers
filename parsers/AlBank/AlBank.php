<?php
require_once(BASE_PATH.'/base_parser.php');

class AlBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.albank.ru/ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<thead>','Золото', 0, true);
		$cnt = trim($data['data']);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));
		$cnt = str_replace('$', 'USD', $cnt);
		$cnt = str_replace('&euro;', 'EUR', $cnt);

		//Unifying <tags>
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Setting default minimal deal amount
			$qty = 1;

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Setting deal amount
			if (preg_match("/\d+/", $symbol, $regs)) {
				$qty = $regs[0];
				$symbol = trim(str_replace($regs[0], '', $symbol));
			}

			//Looking for buying rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			//Looking for selling rate (fourth HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])), $qty);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				$qty, //Minimal deal amount.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>