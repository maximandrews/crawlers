<?php
require_once(BASE_PATH.'/base_parser.php');

class SngBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.sngb.ru/ru/physical_person/fcurcashoperations/exchange/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt		
		$data = findData($data, '<table width="100%" cellspacing="3"','<br />', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Unifying tags
		$cnt = preg_replace("/<strong>[^>]+<\/strong>/is", "", $cnt);
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = trim(strip_tags($cnt, '<td><tr>'));
		$cnt = str_replace("Валюта", '', $cnt);
		$cnt = str_replace("Покупка", '', $cnt);
		$cnt = str_replace("Продажа", '', $cnt);
		$cnt = str_replace("до 5000", '', $cnt);
		$cnt = str_replace("свыше 5000", '', $cnt);
		$cnt = str_replace("<td></td>", '', $cnt);
		$cnt = str_replace("&nbsp;", "", $cnt);

		//Setting $amount to a default value
		$amount = 1;

		//Looping trough all rates rows
		$start = strpos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 1;

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			$symbol = str_replace('ДолларСША', '', $symbol);
			$symbol = str_replace('ЕВРО', '', $symbol);
			$symbol = str_replace('Фунт Стерлингов', '', $symbol);
			$symbol = str_replace('Швейцарский Франк', '', $symbol);

			//Looking for buying rate (second HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			//Skipping rates for big deals
			if ($symbol == "USD " || $symbol == "EUR ") {
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			}

			//Looking for selling rate (fourth HTML table cell)
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

?>