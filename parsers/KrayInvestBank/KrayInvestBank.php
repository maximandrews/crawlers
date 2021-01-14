<?php
require_once(BASE_PATH.'/base_parser.php');

class KrayInvestBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.kibank.ru/exchange/?pid=2');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$ratesData = json_decode($data, true);

		foreach($ratesData['ex_list'] as $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($rate['currency__designation']), //Symbol ID
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1
				$this->CheckRate($rate['buy_price']), //Cash foreign currency buy rate
				$this->CheckRate($rate['sell_price']), //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>