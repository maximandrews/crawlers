<?php
require_once(BASE_PATH.'/base_parser.php');

class SbBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.sbbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<ul class="crhi" id="crh902">','</ul>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Unifying and modifying tags for simplifying search
		$cnt = str_ireplace('<li class="lcr1">', '<tr><td>USD</td><td>', $cnt);
		$cnt = str_ireplace('<li class="lcr3">', '</tr><tr><td>EUR</td><td>', $cnt);
		$cnt = str_ireplace('<li class="lcr5">', '</tr><tr><td>GBP</td><td>', $cnt);
		$cnt = preg_replace("/<li[^>]*>/is", '<td>', $cnt);
		$cnt = str_ireplace('</li>', '</td>', $cnt);
		$cnt = str_replace('-', '0.00', $cnt);
		$cnt .= '</tr>';

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			$rstart = 0;
			//Looking for buying rate (second HTML table cell)
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			$rstart = $data['end'];

			//Looking for buying rate (third HTML table cell)
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			$rstart = $data['end'];

			//Looking for selling rate (third HTML table cell)
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			$rstart = $data['end'];

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>
