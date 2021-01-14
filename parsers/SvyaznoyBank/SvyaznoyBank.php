<?php
require_once(BASE_PATH.'/base_parser.php');

class SvyaznoyBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.svyaznoybank.ru/home/bank/CurrencyRates.aspx');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt		
		$data = findData($data, '<div class="wrapRates">', '<div class="cashlessrates">', 0, true);
		$cnt = trim(strip_tags($data['data'], '<tr><td><abbr>'));

		//Unifying tags
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr class="rate">', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (<abbr> tag)
			$rstart = 0;
			$data = findData($rcnt, '<abbr>', '</abbr>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Skipping second HTML table cell (empty cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for non-cash buying rate (fourth HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyNonCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for non-cash selling rate (fifth HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellNonCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Saving rate
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				$buyNonCash, //Non-Cash foreign currency buy rate
				$sellNonCash //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>