<?php
require_once(BASE_PATH.'/base_parser.php');

class KontrastBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.kontrastbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="right">','class="note"', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);

		//This array will store all rates
		$rates = Array();
		$qty = 1;

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency quantity
			if(preg_match("/От (\d+)/i", $rcnt, $regs))
				$qty = $regs[1];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			if(preg_match("/\(([A-Z]+)\)/i", $symbol, $regs))
				$symbol = $regs[1];

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$rates[$symbol][$qty]['buy'] = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$rates[$symbol][$qty]['sell'] = $this->CheckRate(trim(strip_tags($data['data'])));
		}

		foreach ($rates as $symbol => $qtys) {
			foreach ($qtys as $qty => $rate) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
					$qty, //Deal amount
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