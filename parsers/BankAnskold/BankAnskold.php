<?php
require_once(BASE_PATH.'/base_parser.php');

class BankAnskold extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bankaskold.ru/data/kurs/usd');
		$this->fetchUrl('Rates', 'http://www.bankaskold.ru/data/kurs/eur');
	}

	public function ParseRates($mdata=Array()) {
		//Shifting elements to variable $page.
		while($mdata) {
			$page = array_shift($mdata);
			$cnt = trim($page['pc_content']);

			//Looking for currency symbol (variable name)
			$rstart = 0;
			$data = findData($cnt, 'var ', 'k', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Skipping CB rate (first array element)
			$rstart = 0;
			$data = findData($cnt, "'", "'", $rstart, true);

			//Looking for selling rate (second array element)
			$rstart = $data['end']+1;
			$data = findData($cnt, "'", "'", $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for buying rate (third array element)
			$rstart = $data['end']+1;
			$data = findData($cnt, "'", "'", $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>