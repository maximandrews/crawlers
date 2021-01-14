<?php
require_once(BASE_PATH.'/base_parser.php');

class KubanKredit extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.kubankredit.ru/js/vdata.js');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'var vdata=',']}}]', 0, true);
		$cnt = trim($data['data']).']}}]';

		//Converting to nomal JSON
		$cnt = preg_replace("/([A-Z]{3}):/s", '"\\1":', $cnt);

		//Converting JSON to Array
		$allrates = json_decode($cnt, true);

		foreach($allrates as $branch) {
			if(is_int(stripos($branch['office'], 'Головной офис'))) {
				foreach($branch['course'] as $symbol => $rate) {
					//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
					$this->AddRate(
						$this->GetSymbolID($symbol), //Symbol ID.
						1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
						$rate[0], //Cash foreign currency buy rate
						$rate[1], //Cash foreign currency sell rate
						0, //Non-Cash foreign currency buy rate
						0 //Non-Cash foreign currency sell rate
					);
				}
			}
		}
	}
}

?>