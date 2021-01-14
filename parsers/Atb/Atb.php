<?php
require_once(BASE_PATH.'/base_parser.php');

class Atb extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.atb.su/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div id="currency_some_atb">','<div id="last_time">', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Replacing currency names in Russian with symbols
		$cnt = str_replace('Евро', 'EUR', $cnt);
		$cnt = str_replace('Доллар', 'USD', $cnt);
		$cnt = str_replace('Юань', 'CNY', $cnt);

		//Marking Non-Cash deal values
		$cnt = str_replace('Курс с использованием карт', '<tr>Курс с использованием карт</tr>', $cnt);

		//This array will store all rates
		$rates = Array();
		$dtype = 'Cash';

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			if(is_int(strpos($rcnt, 'Курс с использованием карт'))) {
				$dtype = 'NonCash';
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
				isset($rate['buyNonCash']) ? $rate['buyNonCash']:0, //Non-Cash foreign currency buy rate
				isset($rate['sellNonCash']) ? $rate['sellNonCash']:0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>