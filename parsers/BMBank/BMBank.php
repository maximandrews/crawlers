<?php
require_once(BASE_PATH.'/base_parser.php');

class BMBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bmbank.com.ua/ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table xmlns="" class="curr" cellspacing="0">','</table>', 0, true, false);
		$cnt = trim($data['data']);

		// Remove <caption>
		$cnt = preg_replace('!<caption>.*?</caption>!is', '', $cnt);
		// Allow only <tr> and <td> tags
		$cnt = strip_tags($cnt, '<tr><td>');
		// Remove first <tr>
		$data 	= findData($cnt, '<tr>','</tr>', 0, true, false);
		$cnt 	= substr($cnt, $data['end']+5);

		// Remove all element attributes
		$cnt = preg_replace('/<([\w\d]+)[^>]*>/i', '<\\1>', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt  = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = $data['data'];

			// skip 2nd HTML table cell
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);

			//Looking for buying rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate($data['data']);

			//Looking for selling rate (4th HTML table cell)
			$rstart = $data['end']+5;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate($data['data']);

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