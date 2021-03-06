<?php
require_once(BASE_PATH.'/base_parser.php');

class EcoInvestBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.eco-invest.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'view-currencies','<div class="exchange">', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		// Modifying tags for simplifying search
		$cnt = preg_replace("/<div[^>]+>/is", '<div>', $cnt);
		$cnt = preg_replace("/<span[^>]+>/is", '<span>', $cnt);
		$cnt = preg_replace("/\s+/", " ", $cnt);
		$cnt = preg_replace("/<\/?(td)[^>]*>/is", '', $cnt);
		$cnt = str_replace(' ', '', $cnt);
		$cnt = str_replace('<div><div><div>', '<tr>', $cnt);
		$cnt = str_replace('</div></div></div>', '</tr>', $cnt);
		$cnt = str_replace('</div>', '', $cnt);
		$cnt = str_replace('<div>', '', $cnt);
		$cnt = str_replace('<tr><tr>', '<tr>', $cnt);
		$cnt = str_replace('</tr></tr>', '</tr>', $cnt);

		// Inserting currency symbol names
		$cnt = str_replace('dom-id-1">', '<tr><span>USD</span><span>EUR</span></tr>', $cnt);

		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		$z = 0; // column number
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			$i = 0; //cell number
			//Setting position to search from first char
			$rstart = 0;
			while(is_int($rstart) && $cell = findData($rcnt, '<span>', '</span>', $rstart, true)) {
				$rstart = $cell['end'];
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));

				if($i == 0) {
					if($z == 1) {
						//Setting buy row
						$index = 'buy';
					}elseif($z == 2) {
						//Setting sell row
						$index = 'sell';
					}else
						$index = '';
				}

				if($i >= 0 && empty($index)) {
					//saving to array
					$smnames[$i] = $cellcontent;
				}elseif($i >= 0 && !empty($index)){
					$rates[$smnames[$i]][$index] = $this->CheckRate($cellcontent);
				}
				//Cell # increment
				$i++;
			}
			// Column increment
			$z++;
		}

		foreach($rates as $symbol => $rate) {
			//Saving rate. There is only cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buy'], //Cash foreign currency buy rate
				$rate['sell'], //Cash foreign currency sell rate
				0,//Cash foreign currency buy rate
				0 //Cash foreign currency sell rate
			);
		} 
	}
}

?>