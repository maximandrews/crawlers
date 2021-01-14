<?php
require_once(BASE_PATH.'/base_parser.php');

class BankElita extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bankelita.ru/images/kurs/kurs.js');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$cnt = trim($page['pc_content']);

		//This array will store all rates
		$rates = Array();

		$rates['USD'] = Array();
		//searching for USD buy rate
		$start = stripos($cnt, 'usdbuy()');
		$data = findData($cnt, '"','"', $start, true);
		$rates['USD']['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));

		//searching for USD sell rate
		$start = stripos($cnt, 'usdsale()');
		$data = findData($cnt, '"','"', $start, true);
		$rates['USD']['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));

		$rates['EUR'] = Array();
		//searching for EUR buy rate
		$start = stripos($cnt, 'eurbuy()');
		$data = findData($cnt, '"','"', $start, true);
		$rates['EUR']['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));

		//searching for EUR sell rate
		$start = stripos($cnt, 'eursale()');
		$data = findData($cnt, '"','"', $start, true);
		$rates['EUR']['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));

		foreach($rates as $symbol => $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>