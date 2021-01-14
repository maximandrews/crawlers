<?php
require_once(BASE_PATH.'/base_parser.php');

class DevonCredit extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://devoncredit.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div id="rates">','<script type="text/javascript">', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<em[^>]+>/is", '<em>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<span[^>]+>/is", '<span>', $cnt);

		//Modifying tags for making search
		$cnt = str_replace('<span>USD</span>', '<span><symbol>USD</symbol>', $cnt);
		$cnt = str_replace('<span>EUR</span>', '<span><symbol>EUR</symbol>', $cnt);
		$cnt = str_replace('(ЦБ)</em>', '(ЦБ)</em></span>', $cnt);

		//This array will store all rates
		$rates = Array();

		//Type for buying or selling kind of operation
		$dtype = false;

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<span>', '</span>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			if(is_int(strpos($rcnt, 'Продажа:'))) {
				$dtype = 'sell';
				continue;
			}elseif(is_int(strpos($rcnt, 'Покупка:'))){
				$dtype = 'buy';
				continue;
			}

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<symbol>', '</symbol>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<em>','</em>', $rstart, true);
			$rates[$symbol][$dtype.'Cash'] = $this->CheckRate(trim(strip_tags($data['data'])));
		}

		foreach ($rates as $symbol => $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				0,
				0
			);			
		}
	}
}
?>