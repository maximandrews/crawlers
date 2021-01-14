<?php
require_once(BASE_PATH.'/base_parser.php');

class BIZ extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bisbank.com.ua/site/index.php');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, "<tr><td colspan='6'><img src='./images/zero.gif' border='0' width='2' height='6' title='' alt='' /></td></tr>",'</table>', 0, true, false);
		$cnt = trim($data['data']);

		$cnt = str_replace('Долар США', 'USD', $cnt);
		$cnt = str_replace('Євро', 'EUR', $cnt);
		$cnt = str_replace('Рубль', 'RUB', $cnt);
		// Allow only <tr> and <td> tags
		$cnt = strip_tags($cnt, '<tr><td>');
		// Remove all element attributes
		$cnt = preg_replace('/<([\w\d]+)[^>]*>/i', '<\\1>', $cnt);
		// Remove empty
		$cnt = str_replace(array('<tr><td></td></tr>', '<td></td>'), '', $cnt);
		// Remove all spaces
		$cnt = preg_replace('/\s+/is', '', $cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt  = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			$qty = $symbol == 'RUB' ? 10:100;

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate($data['data'],$qty);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate($data['data'],$qty);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>