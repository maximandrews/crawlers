<?php
require_once(BASE_PATH.'/base_parser.php');

class Obr extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.obr1016.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table width="190" border="0" >','</table>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Replacing currency names with symbols
		$cnt = str_replace('$','USD',$cnt);
		$cnt = str_replace('&euro;','EUR',$cnt);

		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Setting initial value for money type
		$noncash = true;

		//Looping trough all rates rows
		$start = 0;

		//setting type for currency names
		$z = 0;
		while(is_int($start) && $row = findData($cnt, '<tr style="border-top:1 solid;">', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			if(is_int(strpos($row['data'], 'Курс ЦБ')))
				continue;

			//Deleting variable type
			unset($type);

			//cell number
			$i = 0;
			$type = 0;
			//Setting position to search from first char
			$rstart = 0;
			
			while(is_int($rstart) && $cell = findData($rcnt, '>', '</td>', $rstart, true)) {
				$rstart = $cell['end'];
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));
				if($i == 0) {
					if(is_int(strpos($cellcontent, 'USD')) || is_int(strpos($cellcontent, 'EUR'))) {
						//Setting row type 0 - money type
						$type = 0;
						
					}elseif(is_int(strpos($cellcontent, 'Покупка'))) {
						//Setting row type 2 - buy row
						$type = 2;
					
					}elseif(is_int(strpos($cellcontent, 'Продажа'))) {
						//Setting row type 3 - sell row
						$type = 3;
					}
				}

				if($i > 0 && $type == 0) {
					//saving to array
					$smnames[$i] = $cellcontent;
				}elseif($i > 0 && $type > 0){
					$index = '';
					if($type == 2)
						$index = 'buy';
					elseif($type == 3)
						$index = 'sell';

					$index .= 'Cash';
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
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				0,
				0 
			);
		} 
	}
}

?>