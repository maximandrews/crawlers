<?php
require_once(BASE_PATH.'/base_parser.php');

class BystroBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bystrobank.ru/sitecurrency/CurrentExchangeRates.php?cityId-0=Izhevsk');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = trim($page['pc_content']);

		// remove first & last char
		$data = substr($data, 0, -1);
		$data = substr($data, 1);

		// remove javascript stuff
		$data = str_replace(array('banks:[', ']'), '', $data);
		// remove all spaces
		$data = preg_replace('/\s+/', '', $data);
		// replace to double quotes, because otherwise json_decode will return NULL
		$data = str_replace(array('buy', 'sale', "'"), array('"buy"', '"sale"', '"'), $data);

		// decode JSON :)
		$data = json_decode($data);

		//Looping trough all rates rows
		foreach($data as $key => $currency) {
			// select only these
			if(in_array($key, array('eur', 'usd', 'gbp'))) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
					$this->GetSymbolID($key), //Symbol ID.
					1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
					$currency->buy, //Cash foreign currency buy rate
					$currency->sale, //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
			}
		}
	}
}
?>