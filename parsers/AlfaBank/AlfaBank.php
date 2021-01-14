<?php
require_once(BASE_PATH.'/base_parser.php');

class AlfaBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.alfabank.com.ua/ukr/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="currency_div">',' <!-- //currency_div -->', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		// Unifying and modifying tags for simplifying search
		$cnt = preg_replace("/<li[^>]+>/is", '<li>', $cnt);
		$cnt = preg_replace("/<\/?(div|span)[^>]*>/is", '', $cnt);

		//Looping trough all rates rows
		$start = stripos($cnt, '</ul>');
		while(is_int($start) && $row = findData($cnt, '<ul>', '</ul>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<li>', '</li>', $rstart, true);
			$symbol = strtoupper(trim(strip_tags($data['data'])));

			//Passing by central bank rate
			$rstart = $data['end'];
			$data = findData($rcnt, '<li>','</li>', $rstart, true);

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<li>','</li>', $rstart, true);
			list($buyCash, $sellCash) = explode('/', trim(strip_tags($data['data'])));
			$buyCash = $this->CheckRate($buyCash);
			$sellCash = $this->CheckRate($sellCash);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
			$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0,
				0 
			);
		}
	}
}

?>