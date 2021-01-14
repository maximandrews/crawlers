<?php
require_once(BASE_PATH.'/base_parser.php');

class Promregion extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.promregion.ru/tomsk/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="exchange">','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = str_replace('$','USD',$cnt);
		$cnt = str_replace('&euro;','EUR',$cnt);
		$cnt = str_replace('&nbsp;',' - ',$cnt);
		$cnt = str_replace('<td><span>&hellip;</span></td>','',$cnt);

		//This array will store all rates
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		$z = 1; // Number of row to recognize currency
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<th>', '</th>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			// Looking for minimal deal amount for each currency
			if(preg_match("/(\d+) - /", $symbol, $regs) && $z <= 4) {
				$qty = $regs[1];
				$symbol = 'USD';
			}elseif($z > 4) {
				$qty = 1;
				$symbol = 'EUR';
			}

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$rates[$symbol][$qty]['buy'] = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$rates[$symbol][$qty]['sell'] = $this->CheckRate(trim(strip_tags($data['data'])));

			// Row number increment
			$z++;
		}

		foreach ($rates as $symbol => $qtys) {
			foreach ($qtys as $qty => $rate) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID
					$qty, //Minimal deal amount
					$rate['buy'], //Cash foreign currency buy rate
					$rate['sell'], //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
			}
		}
	}
}
?>