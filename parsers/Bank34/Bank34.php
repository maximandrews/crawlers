<?php
require_once(BASE_PATH.'/base_parser.php');

class Bank34 extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.bank34.ua/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="usd">','<a href="javascript:showBlock(\'currency\')"', 0, true, false);
		$cnt = trim('<div class="usd">'.$data['data']);

		// Remove div's and classes
		$cnt = str_replace(array(
			' inviz', ' up', ' down',
			'<div class="first"></div>', '<div class="second"></div>', '<div class="thirth"></div>',
		), '', $cnt);
		// Remove div with class "nprice", which is third col in table
		$cnt = preg_replace('!<div class="nprice">.*?</div>!is', '', $cnt);

		// Replace with <td>
		$cnt = preg_replace('!<div class="name">(.*?)</div>!is', '<td>$1</td>', $cnt);
		$cnt = preg_replace('!<div class="bprice">(.*?)</div>!is', '<td>$1</td>', $cnt);
		$cnt = preg_replace('!<div class="sprice">(.*?)</div>!is', '<td>$1</td>', $cnt);

		// Remove all element attributes
		$cnt = preg_replace('/<([\w\d]+)[^>]*>/i', '<\\1>', $cnt);
		// Remove all spaces
		$cnt = preg_replace('/[\s+]/', '', $cnt);
		// Replace <div>'s with <tr>
		$cnt = str_replace(array('<div>', '</div>'), array('<tr>', '</tr>'), $cnt);
		$cnt = str_replace('UAH/', '', $cnt);

		// Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt  = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate($data['data']);

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
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