<?php
require_once(BASE_PATH.'/base_parser.php');

class NMBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.nmb.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="leftCurrency">','class="currencyChange"', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Unifying <tags>
		$cnt = preg_replace("/<div[^>]+>/is", '<div>', $cnt);
		$cnt = preg_replace("/>\s+/is", '>', $cnt);
		$cnt = preg_replace("/\s+</is", '<', $cnt);
		$cnt = str_replace('</div><div><strong>', '<br clear="all">', $cnt);

		//This array will store all rates
		$rates = Array();

		// Kind of deal
		$dtype = '';

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, 'clear="all">', '<br', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			if(is_int(strpos($rcnt, 'Наличная валюта'))){
				$dtype = 'Cash';
				continue;
			}elseif(is_int(strpos($rcnt, 'Безналичная валюта'))){
				$dtype = 'NonCash';
				continue;
			}

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<div>', '</div>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			// Looking for minimal deal amount for each currency
			if(preg_match("/\((\d+)\)/", $symbol, $regs)) {
				$qty = $regs[1];
				$symbol = trim(str_replace($regs[0], '', $symbol));
			}else {
				$qty = 1;
			}

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<div>', '</div>', $rstart, true);
			$rates[$symbol][$qty]['buy'.$dtype] = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<div>', '</div>', $rstart, true);
			$rates[$symbol][$qty]['sell'.$dtype] = $this->CheckRate(trim(strip_tags($data['data'])));
		}

		foreach ($rates as $symbol => $qtys) {
			foreach ($qtys as $qty => $rate) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID
					$qty, //Minimal deal amount
					$rate['buyCash'], //Cash foreign currency buy rate
					$rate['sellCash'], //Cash foreign currency sell rate
					isset($rate['buyNonCash']) ? $rate['buyNonCash']:0, //Non-Cash foreign currency buy rate
					isset($rate['sellNonCash']) ? $rate['sellNonCash']:0 //Non-Cash foreign currency sell rate
				);
			}
		}
	}
}

?>