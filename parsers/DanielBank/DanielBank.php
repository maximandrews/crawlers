<?php
require_once(BASE_PATH.'/base_parser.php');

class DanielBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.danielbank.kiev.ua/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<!--end currency_tittle-->','<div class="currency_title_bottom">', 0, true);
		$cnt = trim($data['data']);

		// Modifying tags for simplifying search
		$cnt = str_replace(Array('<div class="currency_table_top">','<div class="currency_table_bottom">','<div class="currency_table_tr">'),'<tr>',$cnt);
		$cnt = preg_replace("/<div class=\"currency_table_td\d+\"[^>]*>/is",'<td>',$cnt);
		$cnt = preg_replace("/>\s+/is",'>',$cnt);
		$cnt = preg_replace("/\s+</is",'<',$cnt);
		$cnt = str_replace(Array('</div></div></div>','</div></div>'),'</td></tr>',$cnt);
		$cnt = str_replace('</div>','</td>',$cnt);
		$cnt = str_replace('<div class="table_hide">','',$cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyNonCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellNonCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Saving rate. There is no Cash rates on page. Passing zeros for Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyNonCash, //Cash foreign currency buy rate
				$sellNonCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>