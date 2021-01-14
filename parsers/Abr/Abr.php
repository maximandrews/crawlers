<?php
require_once(BASE_PATH.'/base_parser.php');

class Abr extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://web.abr.ru/sankt-petersburg/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div id="cr">','</div>', 0, true);
		$cnt = preg_replace("/<\/?(b|i|u|br) ?\/?>/is", ' ', $data['data']);
		$cnt = strip_tags(preg_replace("/&#\d+;/is", '', $cnt), '<p><br>');
		$cnt = preg_replace("/\s+/is", ' ', $cnt);
		$start = stripos($cnt, '<p>', 2);
		$cnt = trim(substr($cnt, $start+3, strripos($cnt, '<p>')-($start+3)));
		$rates = explode('<p>', $cnt);

		//Looping trough all rates rows
		foreach($rates as $rate) {
			list($symbol, $sellCash, $buyCash) = explode(' ', trim($rate));
			$symbol = str_replace('$', 'USD', $symbol);
			$symbol = str_replace('&euro;', 'EUR', $symbol);
			$sellCash = $this->CheckRate(trim($sellCash));
			$buyCash = $this->CheckRate(trim($buyCash));
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