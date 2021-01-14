<?php
require_once(BASE_PATH.'/base_parser.php');

class MidzuhoBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('LastRatesPage', 'http://www.mizuhobank.com/russia/ru/rate/index.html');
	}

	public function ParseLastRatesPage($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];
		$data = findData($data, '<li class="external">', '</li>', 0, true);
		$url = trim($data['data']);
		$url = findData($url, 'a href="', '"', 0, true);
		$this->fetchUrl('Rates', 'http://www.mizuhobank.com'.trim(strip_tags($url['data'])));
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
		$data = findData($data, '<h2 class="h2Tit">', 'Организации', 0, true);
		$cnt = trim($data['data']);

		//Unifying tags
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = str_replace('<th class="center">', '<td>', $cnt);
		$cnt = str_replace('</th>', '</td>', $cnt);

		//Setting default value for variable $j;
		$j = 0;

		//Looping trough all rates rows
		//Position of start of looking for currency name
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<table class="type1" summary="">', '</table>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 0;
			$rstart = 0;

			//Looking for any data in HTML table subcells)
			while(is_int($rstart) && $trow = findData($rcnt, '<tr>', '</tr>', $rstart, true)){
				$rstart = $trow['end'];
				$tcnt = $trow['data'];
				$n = 0;

				$tstart = 0;
				while(is_int($tstart) && $data = findData($tcnt, '<td>', '</td>', $tstart, true)){
					$tstart = $data['end'];

					switch ($i) {
						case 0:
							if(!isset($rates[$n])) $rates[$n] = Array();
							$rates[$n]['symbol'] = trim(strip_tags($data['data']));
							$rates[$n]['qty'] = $rates[$n]['symbol'] == 'JPY' ? 100:1;
							break;
						case 1:
							switch($j) {
								case 0:
									$rates[$n]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])), $rates[$n]['qty']);
									break;
								case 1:
									$rates[$n]['buyNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])), $rates[$n]['qty']);
									break;
							}
							break;
						case 2:
							switch($j) {
								case 0:
									$rates[$n]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])), $rates[$n]['qty']);
									break;
								case 1:
									$rates[$n]['sellNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])), $rates[$n]['qty']);
									break;
							}
							break;
					}

					$n++;
				}

				//Incrementing variable $i
				$i++;
			}

			//Incrementing variable $j
			$j++;
		}

		//Saving rate.
		foreach($rates as $rate){
			$this->AddRate(
				$this->GetSymbolID($rate['symbol']), //Symbol ID.
				$rate['qty'], //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				$rate['buyNonCash'], //Non-Cash foreign currency buy rate
				$rate['sellNonCash'] //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>