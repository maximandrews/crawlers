<?php
require_once(BASE_PATH.'/base_parser.php');

class NstBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.nstbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<td class="p1">', '</table>', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Variable for deal type
		$dtype = '';

		//Array for rates
		$rates = Array();

		//Looping trough all rates rows
		$start = stripos($cnt, '<div>');
		while(is_int($start) && $row = findData($cnt, '<div', '/div>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			/*
				If function 'if' analysed $rcnt for 'Продажа' and found it. 
				Then function 'stripos' found their position and function 'is_int' is true, $dtype = 'sell'.
			*/
			if (is_int(stripos($rcnt,'Продажа'))){
				$dtype = 'Sell';
				continue;
			} elseif (is_int(stripos($rcnt,'Покупка'))){
				$dtype = 'Buy';
				continue;
			} elseif (is_int(stripos($rcnt,'Курс Центрального Банка'))){
				break;
			} 

			//Finding currency symbol and rate
			$rstart = 0;
			$data = findData($rcnt, '>', '</b>', $rstart, true);
			$symrate = explode ('<b>',$data['data']);
			$symbol = trim(strip_tags($symrate[0]));
			$rate = trim(strip_tags($symrate[1]));
			$rate = $this->CheckRate(trim(strip_tags($symrate[1])));
			$rates[$symbol][$dtype] = $rate;
		}

		foreach ($rates as $symbol => $cash) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$cash['Buy'], //Cash foreign currency buy rate
				$cash['Sell'], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>