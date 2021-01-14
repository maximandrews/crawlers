<?php
require_once(BASE_PATH.'/base_parser.php');

class Rusnarbank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.rusnarbank.com/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<td colspan="3" id="curses">','</table>', 0, true);
		$cnt = trim($data['data']);

		//modifying some tags to make searching more simple 
		$cnt = preg_replace("/<tr[^>]+>/is",'<tr>', $cnt);
		$cnt = preg_replace("/<div[^>]+>/is",'<div>', $cnt);
		$cnt = preg_replace("/<td\s+>/is",'<td>', $cnt);
		$cnt = trim(substr($cnt, stripos($cnt, '</tr>')+5));
		$cnt = preg_replace("/>\s+/is",'>', $cnt);
		$cnt = preg_replace("/\s+</is",'<', $cnt);
		$cnt = str_replace('<td colspan="3">','<td>', $cnt);
		$cnt = str_replace('</div></td></tr><tr><td><div>','</td><td>', $cnt);
		$cnt = str_replace('</div></td><td><div>','</td><td>', $cnt);
		$cnt = str_replace('</td></tr><tr><td><div>','</td><td>', $cnt);
		$cnt = str_replace('</div></td></tr><tr><td></td>','</td>', $cnt);
		$cnt = str_replace('</div></td></tr><tr><td></td><td><div>',' ', $cnt);
		$cnt = str_replace('</td><td><div>',' ', $cnt);

		//Looping trough all rates rows
		$start = 0;
		$i = 1; // type of currency symbol
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			
			if(is_int(strpos($rcnt, 'Евро'))) $symbol = 'EUR';
			if(is_int(strpos($rcnt, 'Доллар'))) $symbol = 'USD';

			//Looking for buying rate (second HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags(str_replace('руб.', '', $data['data']))));
			
			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>','</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags(str_replace('руб.', '', $data['data']))));

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0,
				0 
			);
			$i++;
		}
	}
}

?>