<?php
require_once(BASE_PATH.'/base_parser.php');

class MCBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.mcbank.ru/rate/rate.txt');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		 */
		$page = array_shift($mdata);
		$data = trim($page['pc_content']);
		
		// Remove date
		$data = explode("\n", $data);
		array_shift($data);

		// Add rates to array
		$i = 0;
		$rates = array();
		foreach($data as $currency) {
			// explode buy / sell
			$rate = explode('/', $currency);
			// currency symbol
			$symbol = ($i == 0 ? 'usd' : 'eur');
			// add to array
			$rates[$symbol] = array(
				'buy'	=> $rate[0],
				'sell'	=> $rate[1],
			);
			
			// iterate
			$i++;
		}
		
		// Parse rates
		foreach($rates as $symbol => $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buy'], //Cash foreign currency buy rate
				$rate['sell'], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>