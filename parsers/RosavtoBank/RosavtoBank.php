<?php
require_once(BASE_PATH.'/base_parser.php');

class RosavtoBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.rosavtobank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table class="tab_exchange">', '<div class="button button_simple">', 0, true);
		$cnt = trim($data['data']);

		//Unifying tags
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<tr[^>]+>/is", '<td>', $cnt);

		//Setting primary value for variable $i;
		$i = 0;

		//Looping through tables (cash and non-cash)
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tbody>', '</tbody>', $start, true)) {
			$start = $row['end'];

			$j = 0;

			$tstart = 0;
			while(is_int($tstart) && $trow = findData($row['data'], '<tr>', '</tr>', $tstart, true)) {
				$rcnt = $trow['data'];
				$tstart = $trow['end'];

				if(!isset($rates[$j])) $rates[$j] = Array();
				$rates[$j]['buyNonCash'] = 0;
				$rates[$j]['sellNonCash'] = 0;

				//Looking for currency symbol (first HTML table cell)
				$rstart = 0;
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$data = findData($data['data'], '<span class="cur_name">', '</span>', $rstart, true);
				$rates[$j]['symbol'] = trim(strip_tags($data['data']));

				//Looking for buying rate (second HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				switch ($i) {
					case 0:
						$rates[$j]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 1:
						$rates[$j]['buyNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Looking for selling rate (third HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				switch ($i) {
					case 0:
						$rates[$j]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 1:
						$rates[$j]['sellNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Incrementing variable $j
				$j++;
			}

			//Incrementing variable $i
			$i++;
		}

		foreach($rates as $rate) {
			$this->AddRate(
				$this->GetSymbolID($rate['symbol']), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				$rate['buyNonCash'], //Cash foreign currency buy rate
				$rate['sellNonCash'] //Cash foreign currency sell rate
			);
		}
	}
}

?>