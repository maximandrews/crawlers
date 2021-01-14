<?php
require_once(BASE_PATH.'/base_parser.php');

class BenefitBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.benefitbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt		
		$data = findData($data, '<th>', '</table>', 0, true);
		$cnt = trim($data['data']);

		//Setting default value for variable $j;
		$j = 1;

		//Looping trough all rates rows
		//Position of start of looking for currency name
		$start = strpos($cnt, '</tr>');
		if(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 1;
			$rstart = 0;

			//Looking for any data in HTML table subcells)
			while(is_int($rstart) && $data = findData($rcnt, '<td>', '</td>', $rstart, true)){
				$rstart = $data['end'];

				if(!isset($rates[$i-1])) $rates[$i] = Array();

				switch ($j%3) {
					case 1:
						$i++;
						$rates[$i-1]['symbol'] = trim(strip_tags($data['data']));
						break;
					case 2:
						$rates[$i-1]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 0:
						$rates[$i-1]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Incrementing variable $j
				$j++;
			}
		}

		foreach($rates as $rate){
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($rate['symbol']), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>