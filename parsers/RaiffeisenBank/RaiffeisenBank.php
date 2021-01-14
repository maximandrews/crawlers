<?php
require_once(BASE_PATH.'/base_parser.php');

class RaiffeisenBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'https://www.raiffeisen.ru/mail/xml-currency-rates.do');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$cnt = $page['pc_content'];

		$xml = simplexml_load_string($cnt);
		$rates = (array) $xml->exchange;
		$rates = $rates['currency'];

		//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
		foreach ($rates as &$rate) {
			$rate = (array) $rate;
			$amount = isset($rate["amount"]) && preg_match("/^\d+$/", $rate["amount"]) && $rate["amount"] > 0 ? $rate["amount"]:1;
			$this->AddRate(
				$this->GetSymbolID(trim($rate['name'])), //Symbol ID.
				$amount, //Minimal deal amount. By default this value should be 1.
				$this->CheckRate(trim($rate['buy']),$amount), //Cash foreign currency buy rate
				$this->CheckRate(trim($rate['sell']),$amount), //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>