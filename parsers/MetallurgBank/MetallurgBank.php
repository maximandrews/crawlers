<?php
require_once(BASE_PATH.'/base_parser.php');

class MetallurgBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.metallurgbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'exchange_rates','</table>', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<th[^>]+>/is", '<td>', $cnt);
		$cnt = str_replace ('<th>','<td>',$cnt);
		$cnt = str_replace ('</th>','</td>',$cnt);

		//Modifying currency symbol name
		$cnt = str_replace ('EURO','EUR',$cnt);
		
		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Number of table column
		$z = 1;

		//Looping trough all rates rows
		$start = strpos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//cell number
			$i = 0;

			//Setting position to search from first char
			$rstart = 0;
			while(is_int($rstart) && $cell = findData($rcnt, '<td>', '</td>', $rstart, true)) {
				$rstart = $cell['end'];
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));

				//Looking for currency symbols
				if($z == 1) {
					//saving to array
					$smnames[$i] = $cellcontent;
				}else {
					$index = '';

					//Looking for buying rate
					if($z == 2)
						$index = 'buy';

					//Looking for selling rate
					elseif($z == 3)
						$index = 'sell';

					$rates[$smnames[$i]][$index] = $this->CheckRate($cellcontent);
				}
				//Cell # increment
				$i++;
			}
			// Column increment
			$z++;
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