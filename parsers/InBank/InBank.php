<?php
require_once(BASE_PATH.'/base_parser.php');

class InBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://currency.in-bank.ru/NewValute.php');
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
		$data = findData($data, '<div style="height:75px;">', '<div class="currency" id="currency4"', 0, true);
		$cnt = trim($data['data']);

		//Unifying tags
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = str_replace('&darr;', '', $cnt);
		$cnt = str_replace('&uarr;', '', $cnt);

		//Setting primary value for variable $i;
		$i = 0;

		//Looping trough all rates rows
		$start = 0;

		while(is_int($start) && $row = findData($cnt, '<div id="twoTable">', '</table></div></div>', $start, true)) {
			$start = $row['end'];

			//Setting default value for rates variables ($amount, $buyCash, $sellCash)
			$buyCash = 0;
			$sellCash = 0;
			$buyNonCash = 0;
			$sellNonCash = 0;
			$j = 0;
			$amount = 1;

			$tstart = 0;
			while(is_int($tstart) && $trow = findData($row['data'], '<table class="row" cellpadding="1" cellspacing="1">', '</table>', $tstart, true)) {
				$rcnt = $trow['data'];
				$tstart = $trow['end'];
				if(!isset($rates[$j])) $rates[$j] = Array();
				if ($i != 2) {
					if(!isset($rates[$j][$i])) $rates[$j][$i] = Array();
					$rates[$j][$i]['amount'] = 1;
				}

				//Looking for currency symbol (first HTML table cell)
				$rstart = 0;
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				if ($i != 2)
					$rates[$j][$i]['symbol'] = trim(strip_tags($data['data']));

				//Looking for buying rate (second HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				switch ($i) {
					case 0:
						$rates[$j][$i]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 1:
						$rates[$j][$i]['amount'] = 100000;
						$rates[$j][$i]['buyCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 2:
						$rates[$j][0]['buyNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						$rates[$j][1]['buyNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Looking for selling rate (third HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				switch ($i) {
					case 0:
						$rates[$j][$i]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 1:
						$rates[$j][$i]['amount'] = 100000;
						$rates[$j][$i]['sellCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 2:
						$rates[$j][0]['sellNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						$rates[$j][1]['sellNonCash'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Incrementing variable $j
				$j++;
			}

			//Incrementing variable $i
			$i++;
		}

		foreach($rates as $rate) {
			foreach ($rate as $trate) {
				$this->AddRate(
					$this->GetSymbolID($trate['symbol']), //Symbol ID.
					$trate['amount'], //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
					$trate['buyCash'], //Cash foreign currency buy rate
					$trate['sellCash'], //Cash foreign currency sell rate
					$trate['buyNonCash'], //Cash foreign currency buy rate
					$trate['sellNonCash'] //Cash foreign currency sell rate
				);
			}
		}
	}
}

?>