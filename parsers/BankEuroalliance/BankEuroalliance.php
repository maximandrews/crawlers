<?php
require_once(BASE_PATH.'/base_parser.php');

class BankEuroalliance extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.euroalliance.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<td class="first_td" colspan="2">', '</table>', 0, true);
		$cnt = trim($data['data']);

		//Change encording
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Unifying <tags>, only first posible and no matter which letter sensitive. 
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/\s+</is", '<', $cnt);
		$cnt = preg_replace("/>\s+/is", '>', $cnt);
		$cnt = str_replace('</tr><tr><td>Покупка</td>', '', $cnt);
		$cnt = str_replace('</tr><tr><td>Продажа</td>', '', $cnt);

		//Looping trough all rates rows
		$start = stripos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell);
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			//Finding all simbols which are digits, but others with are not digits replace to space
			$qty = preg_replace("/\D+/s", '', $symbol);
			//Finding all simbols which are leters and replace to space
			$symbol = preg_replace("/[^A-Z]+/is", '', $symbol);
			//Substitute EURO to EUR
			$symbol = str_replace("EURO", 'EUR', $symbol);

			//Looking or buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

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