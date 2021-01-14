<?php
require_once(BASE_PATH.'/base_parser.php');

class MVBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.mvbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="kurs">','</div>', 0, true);
		$cnt = trim($data['data']);

		//Unifying and modifying tags for simplifying search
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<\/?(span|strong)[^>]*>/is", '', $cnt);
		$cnt = str_replace('/RUB','',$cnt);
		$cnt = str_replace(' ','',$cnt);
		$cnt = str_replace('<BR>USD<b>','<BR><b>USD<\b><b>',$cnt);
		$cnt = str_replace('EUR<b>','<br><b>EUR<\b><b>',$cnt);
		$cnt = str_replace('</b>','<\b>',$cnt);
		$cnt = str_replace('</br>','<\br>',$cnt);
		$cnt = str_replace('<br/>','<\br>',$cnt);
		$cnt = str_replace('/','<\b><b>',$cnt);

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<br>', '<\br>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<b>', '<\b>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<b>', '<\b>', $rstart, true);
			$buyNonCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Looking for selling rate (third HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<b>', '<\b>', $rstart, true);
			$sellNonCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Saving rate. There is no Cash rates on page. Passing zeros for Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyNonCash, //Cash foreign currency buy rate
				$sellNonCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0//Non-Cash foreign currency sell rate
			);
		}
	}
}

?>