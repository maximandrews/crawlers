<?php
require_once(BASE_PATH.'/base_parser.php');

class StroyKreditBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.stroycredit.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table class="left-currency" border="0" width="100%" align="left">','</tbody>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Setting initial value for money type
		$noncash = true;

		//Looping trough all rates rows
		$start = strpos($cnt, '</tr>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Deleting variable type
			unset($type);

			//cell number
			$i = 0;

			//Setting position to search from first char
			$rstart = 0;
			while(is_int($rstart) && $cell = findData($rcnt, '>', '</td>', $rstart, true)) {
				$rstart = $cell['end']+5;
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));

				if($i == 0) {
					if(preg_match("/\d{2}\.\d{2}\.\d{4}/s", $cellcontent)) {
						//Setting row type 0 - symbol names
						$type = 0;
					}elseif(is_int(strpos($row['data'], 'colspan'))) {
						//Setting row type 1 - money type
						$type = 1;
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
				}elseif($type == 1){
					if(is_int(strpos($cellcontent, 'Наличный обмен'))) {
						$noncash = false;
						continue;
					}elseif(is_int(strpos($cellcontent, 'Безналичная конвертация'))) {
						$noncash = true;
						continue;
					}else
						break 2;
				}elseif($i > 0){

					$index = '';
					if($type == 2)
						$index = 'buy';
					elseif($type == 3)
						$index = 'sell';

					if($noncash == false)
						$index .= 'Cash';

					$rates[$smnames[$i]][$index] = $this->CheckRate($cellcontent);
				}

				//Cell # increment
				$i++;
			}
		}

		foreach($rates as $symbol => $rate) {
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$rate['buyCash'], //Cash foreign currency buy rate
				$rate['sellCash'], //Cash foreign currency sell rate
				$rate['buy'], //Non-Cash foreign currency buy rate
				$rate['sell'] //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>