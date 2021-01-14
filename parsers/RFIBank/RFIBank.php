<?php
require_once(BASE_PATH.'/base_parser.php');

class RFIBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.rficb.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt		
		$data = findData($data, '<table>', '<div id="kurs_in">', 0, true);
		$cnt = trim($data['data']);

		//Setting default value for variable $amount
		$amount = 1;

		//Unifying tags
		$cnt = str_replace('&nbsp;', '', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tbody>', '</tbody>', $start, true)) {
			$start = $row['end'];

			$tstart = 0;
			while(is_int($tstart) && $trow = findData($row['data'], '<tr>', '</tr>', $tstart, true)) {
				$rcnt = $trow['data'];
				$tstart = $trow['end'];
				if (strpos($rcnt, '<div') != 0) {
					if (strpos($rcnt, '>10000') != 0)
						$amount = 10000;
					continue;
				}

				//Looking for currency symbol (first HTML table cell)
				$rstart = 0;
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$symbol = trim(strip_tags($data['data']));

				//Looking for buying rate (second HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

				//Looking for selling rate (third HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
					$this->GetSymbolID($symbol), //Symbol ID.
					$amount, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
					$buyCash, //Cash foreign currency buy rate
					$sellCash, //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
			}
		}
	}
}
?>