<?php
require_once(BASE_PATH.'/base_parser.php');

class Neyvabank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.neyvabank.ru/uslugi/corp/10/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'var cur = eval(\'(',')\');', 0, true);
		$cnt = trim($data['data']);
		$rates = json_decode($cnt, true);

		$mrates = isset($rates["Екатеринбург"]) ? $rates["Екатеринбург"]: false; //removing unnecessary symbols make searching more simple

		//Looping trough all rates rows
		if(is_array($mrates)) {
			foreach($mrates as $symbol => $rate) {
				$buyCash = $this->CheckRate(trim(strip_tags($rate['buy'])));
				$sellCash = $this->CheckRate(trim(strip_tags($rate['sell'])));

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
}

?>