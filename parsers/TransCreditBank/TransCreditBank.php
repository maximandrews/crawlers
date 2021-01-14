<?php
require_once(BASE_PATH.'/base_parser.php');

class TransCreditBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.tcb.ru/currency_rates.ihtml?city=32330');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		// decode JSON to array
		$data = json_decode($page['pc_content'], true);
		$data = $data['rates'];

		$rates = Array();
		foreach($data as $dtype => $rts) {
			foreach($rts as $rate) {
				$rates[$rate['currency']]['buy'.$dtype] = $this->CheckRate(trim($rate['buy']));
				$rates[$rate['currency']]['sell'.$dtype] = $this->CheckRate(trim($rate['sale']));
			}
		}
		
		// Parse & Save rates
		foreach($rates as $symbol => $rate) {
			// Saving rate
			$this->AddRate(
				$this->GetSymbolID($symbol), // Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buycash'], //Cash foreign currency buy rate
				$rate['sellcash'], //Cash foreign currency sell rate
				$rate['buynonCash'], //Non-Cash foreign currency buy rate
				$rate['sellnonCash'] //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>