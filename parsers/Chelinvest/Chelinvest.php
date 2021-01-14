<?php
require_once(BASE_PATH.'/base_parser.php');

class Chelinvest extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.chelinvest.ru/currency/val.html');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div id="content">','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		
		// Replacing currency names in Russian with symbols
		$cnt = str_replace('Доллаp США','USD',$cnt);
		$cnt = str_replace('Евро','EUR',$cnt);
		$cnt = str_replace('Английский фунт стеpлинг','GBP',$cnt);
		$cnt = str_replace('Казахский тенге','KZT',$cnt);
		$cnt = str_replace('Шведская кpона','SEK',$cnt);
		$cnt = str_replace('Японская йена','JPY',$cnt);
		$cnt = str_replace('Китайский юань','CNY',$cnt);
		$cnt = str_replace('Швейцаpский фpанк','CHF',$cnt);

		//This array will store all rates
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		$z = 0; // Number of row to recognize currency
		$symbol = ''; // Currency symbol
		$qty = ''; // Deal amount range
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;

			$amount = 1;
			//Looking for minimal deal amount and currency symbols
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);

			//Processing currencies with second and more deal amount ranges and setting their symbols
			if(preg_match("/(\d+)-/", $rcnt, $regs) && $z > 1 && $z <= 6) {
				$qty = $regs[1];
				$symbol = 'USD';
			}elseif(preg_match("/(\d+)-/", $rcnt, $regs) && $z > 7 && $z <= 12) {
				$qty = $regs[1];
				$symbol = 'EUR';

			//Processing currencies with the first or only minimal deal amount and setting their symbols
			} elseif ($z > 12 || $z==1 || $z ==7) {
				$symbol = trim(strip_tags($data['data']));
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$qty = trim(strip_tags($data['data']));
					if ($z==1 || $z ==7) {
						$qty = 1;
					}else
						$amount = $qty;
			} 

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$rates[$symbol][$qty]['buy'] = $this->CheckRate(trim(strip_tags($data['data'])), $amount);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$rates[$symbol][$qty]['sell'] = $this->CheckRate(trim(strip_tags($data['data'])), $amount);
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