<?php
require_once(BASE_PATH.'/base_parser.php');

class TIBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.transinvestbank.ru/');
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
		$data = findData($data, "<div id='valute_tabcache'>", "_tabCB'>", 0, true);
		$cnt = trim($data['data']);

		//Unifying tags
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = str_replace('<tr>', '<td <tr>', $cnt);
		$cnt = str_replace('</table>', '<td <tr>', $cnt);

		//Setting default value for variable $j
		//$j is for counting tables
		$j = 0;

		//Looping trough all tables
		//Position of start of looking for tables
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<table', "<div id='valute", $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 0;
			$pstart = 0;

			while(is_int($start) && $prow = findData($rcnt, '<div', '<tr>', $pstart, true)) {
				$pcnt = $prow['data'];
				$pstart = $prow['end'];

				if(!isset($rates[$i])) $rates[$i] = Array();

				//Looking for currency symbol (first HTML table cell)
				$rstart = 0;
				$data = findData($pcnt, '>', '<td', $rstart, true);
				$rates[$i]['symbol'] = trim(strip_tags($data['data']));
				$rates[$i]['symbol'] = substr($rates[$i]['symbol'], 0, 3);

				//Looking for buying rate (second HTML table cell)
				$rstart = $data['end'];
				$data = findData($pcnt, 'class=', '<td', $rstart, true);
				switch ($j) {
					case 0:
						$rates[$i]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 1:
						$rates[$i]['buyNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Looking for selling rate (third HTML table cell)
				$rstart = $data['end'];
				$data = findData($pcnt, 'class=', '<td', $rstart, true);
				switch ($j) {
					case 0:
						$rates[$i]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 1:
						$rates[$i]['sellNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Incrementing variable $i
				$i++;
			}

			//Incrementing variable $j
			$j++;
		}

		//Saving rate
		foreach($rates as $rate){
			$this->AddRate(
				$this->GetSymbolID($rate['symbol']), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				$rate['buyNonCash'], //Non-Cash foreign currency buy rate
				$rate['sellNonCash'] //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>