<?php
require_once(BASE_PATH.'/base_parser.php');

class EcBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.ec-bank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<thead>','</tbody>', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = str_replace ('<th>','<td>',$cnt);
		$cnt = str_replace ('</th>','</td>',$cnt);
		$cnt = str_replace ('<tbody>','</tr>',$cnt);

		//Modifying currency symbol name
		$cnt = str_replace ('EURO','EUR',$cnt);

		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//cell number
			$i = 0;

			$index = '';

			if(is_int(strpos($rcnt, 'Покупка'))) //Looking for buying rate
				$index = 'buy';
			elseif(is_int(strpos($rcnt, 'Продажа'))) //Looking for selling rate
				$index = 'sell';

			//Setting position to search from first char
			$rstart = stripos($rcnt, '</td>');
			while(is_int($rstart) && $cell = findData($rcnt, '<td>', '</td>', $rstart, true)) {
				$rstart = $cell['end'];
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));

				//Looking for currency symbols
				if(is_int(strpos($rcnt, '<td></td>'))) {
					//saving to array
					$smnames[$i] = $cellcontent;
				}else
					$rates[$smnames[$i]][$index] = $this->CheckRate($cellcontent);

				//Cell # increment
				$i++;
			}
		}

		foreach($rates as $symbol => $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buy'], //Cash foreign currency buy rate
				$rate['sell'], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>