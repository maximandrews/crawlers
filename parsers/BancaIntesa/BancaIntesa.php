<?php
require_once(BASE_PATH.'/base_parser.php');

class BancaIntesa extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bancaintesa.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<h2>КУРС ВАЛЮТ </h2>','<div class="curBox">', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);

		//Modifying symbols for making search
		$cnt = str_replace('<div id="nal">', '<tr><div id="nal"></tr>', $cnt);
		$cnt = str_replace('<div id="beznal"', '<tr><div id="beznal"</tr>', $cnt);

		//This array will store all rates
		$rates = Array();
		$dtype = false;

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Setting money type for bank transaction
			if(is_int(strpos($rcnt, 'div id="nal"'))) {
				$dtype = 'Cash';
				continue;
			} elseif(is_int(strpos($rcnt, 'div id="beznal"'))){
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
			//Saving rate
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID
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