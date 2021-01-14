<?php
require_once(BASE_PATH.'/base_parser.php');

class BankSolidarnost extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.solid.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<thead','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));
		$cnt = trim(findData($cnt, '>','ЦБ', 0, true)['data']);
		$cnt = str_replace('th>', 'td>', $cnt);
		$cnt = str_replace('<th ', '<td ', $cnt);
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<img[^>]+images\//is", '', $cnt);
		$cnt = preg_replace("/\.gif[^>]+\/>/is", '', $cnt);

		//All rates will be stored in this array
		$rates = Array();

		//Setting default value for variable $j;
		$j = 0;

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 0;
			$rstart = strpos($rcnt, '</td>');

			//Looking for any data in HTML table cells)
			while(is_int($rstart) && $data = findData($rcnt, '<td>', '</td>', $rstart, true)){
				$rstart = $data['end'];

				if(!isset($rates[$i])) $rates[$i] = Array();

				switch ($j) {
					case 0:
						$rates[$i]['symbol'] = trim(strip_tags($data['data']));
						//Replacing image urls with currency names which are set manually
						$rates[$i]['symbol'] = str_replace('7_03', 'USD', $rates[$i]['symbol']);
						$rates[$i]['symbol'] = str_replace('7_06', 'EUR', $rates[$i]['symbol']);
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