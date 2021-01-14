<?php
require_once(BASE_PATH.'/base_parser.php');

class Jsbni extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.jsbni.kiev.ua/index.php?Language=ukr');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<tr valign="top">','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		// Unifying and modifying tags for simplifying search
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<img[^>]*>/is", '', $cnt);
		$cnt = str_replace('<td></td>','',$cnt);

		// Replacing currency names in Ukrainian with symbols
		$cnt = str_replace('Долари США','USD',$cnt);
		$cnt = str_replace('Євро','EUR',$cnt);
		$cnt = str_replace('Рублі','RUB',$cnt);

		//Looping trough all rates rows
		$start = stripos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = strtoupper(trim(strip_tags($data['data'])));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			list($buyCash, $sellCash) = explode('/', trim(strip_tags($data['data'])));
			$buyCash = $this->CheckRate(trim($buyCash));
			$sellCash = $this->CheckRate(trim($sellCash));

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>