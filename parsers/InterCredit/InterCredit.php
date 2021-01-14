<?php
require_once(BASE_PATH.'/base_parser.php');

class InterCredit extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.intercredit.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="lineForm">','<div id="blck-branch-5"', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = str_replace('Безналичный курс', '<tr>Безналичный курс</tr>', $cnt);
		$cnt = str_replace('Наличный курс', '<tr>Наличный курс</tr>', $cnt);

		//This array will store all rates
		$rates = Array();
		$dtype = false;

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for kind of deal
			if(is_int(strpos($rcnt, 'Наличный курс'))) {
				$dtype = 'Cash';
				continue;
			}elseif(is_int(strpos($rcnt, 'Безналичный курс'))){
				$dtype = '';
				continue;
			}

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$rates[$symbol]['buy'.$dtype] = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt,  '<td>','</td>', $rstart, true);
			$rates[$symbol]['sell'.$dtype] = $this->CheckRate(trim(strip_tags($data['data'])));
		}

		foreach ($rates as $symbol => $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
			$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				$rate['buy'], //Non-Cash foreign currency buy rate
				$rate['sell'] //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>