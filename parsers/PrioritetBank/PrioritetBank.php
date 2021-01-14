<?php
require_once(BASE_PATH.'/base_parser.php');

class PrioritetBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.prioritetbank.ru/private_clients/obmen_valyuti/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<tbody>','</tbody>', 0, true);
		$cnt = trim($data['data']);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = str_replace('Курс ','',$cnt);

		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 0; //cell number
			$type = 0; //  row type (output column name)
			//Setting position to search from first char
			$rstart = 0;

			while(is_int($rstart) && $cell = findData($rcnt, '<td>', '</td>', $rstart, true)) {
				$rstart = $cell['end'];
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));
				if($i == 0) {
					if(is_int(strpos($cellcontent, 'USD')) || is_int(strpos($cellcontent, 'EUR'))) {
						//Setting row type 0 - money type
						$type = 0;
						
					}elseif(is_int(strpos($cellcontent, 'Покупка'))) {
						//Setting row type 2 - buy row
						$type = 1;
					
					}elseif(is_int(strpos($cellcontent, 'Продажа'))) {
						//Setting row type 3 - sell row
						$type = 2;
					}
				}

				if($i > 0 && $type == 0) {
					//saving to array
					$smnames[$i] = $cellcontent;
				}elseif($i > 0 && $type > 0){
					$index = '';
					if($type == 1)
						$index = 'buy';
					elseif($type == 2)
						$index = 'sell';
					$rates[$smnames[$i]][$index] = $this->CheckRate($cellcontent);
				}
				//Cell # increment
				$i++;
			}
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