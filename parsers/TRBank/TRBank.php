<?php
require_once(BASE_PATH.'/base_parser.php');

class TRBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.trbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];
		$rates = Array ();

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt		
		$data = findData($data, '<div class="our-bank">','</div>', 0, true);
		$data = findData($data['data'], '<tr>','</tr>', strpos($data['data'], '</tr>'), true);
		$cnt = trim($data['data']);

		//Setting default value for variable $j;
		$j = 0;

		//Looping trough all rates rows
		//Position of start of looking for currency name
		$start = strpos($cnt, '</td>');
		while(is_int($start) && $row = findData($cnt, '<td', '</td>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 0;
			$rstart = 0;;

			//Looking for any data in HTML table subcells)
			while(is_int($rstart) && $data = findData($rcnt, '>', '<', $rstart, true)){
				$rstart = $data['end'];

				if(!isset($rates[$i])) $rates[$i] = Array();

				switch ($j) {
					case 0:
						$rates[$i]['symbol'] = trim(strip_tags($data['data']));
						break;
					case 1:
						$rates[$i]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 2:
						$rates[$i]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}
				
				//Incrementing variable $i
				$i++;
			}

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values

			//Incrementing variable $j
			$j++;
		}

		foreach($rates as $rate){
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