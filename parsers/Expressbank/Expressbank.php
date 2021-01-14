<?php
require_once(BASE_PATH.'/base_parser.php');

class Expressbank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.express-bank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'var currency =',';', 0, true);
		$cnt = trim($data['data']);

		//parsing JSON rates
		$orates = json_decode($cnt, true);
		$rates = Array();
		if(is_array($orates)) {
			foreach($orates as $symbol => $dtypes) {
				if(!isset($rates[$symbol])) $rates[$symbol] = Array();

				foreach($dtypes as $dtype => $deals) {
					foreach($deals as $qty => $rate) {
						$qty = $qty < 1 ? 1:$qty;
						$rates[$symbol][$qty][$dtype] = $this->CheckRate($rate['curse'], 1);
					}
				}
			}
		}

		if(is_array($rates))
			foreach($rates as $symbol => $deals) {
				foreach($deals as $qty => $rate) {
					//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
					$this->AddRate(
						$this->GetSymbolID($symbol), //Symbol ID.
						$qty, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
						$rate['buy'], //Cash foreign currency buy rate
						$rate['sell'], //Cash foreign currency sell rate
						0,
						0
					);
				}
			}
	}
}

?>