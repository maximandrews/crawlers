<?php
require_once(BASE_PATH.'/base_parser.php');

class Chelindbank extends baseParser {
	public function Init() {
		/*First parameter is function name without prefix 'Parse'.
		Second parameter is URL to fetch.*/
		$this->fetchUrl('Rates', 'http://www.chelindbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<TABLE BORDER=0 CELLPADDING=1 CELLSPACING=1 CLASS=xtable','</TABLE>', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));

		//Preparing array for currency symbol names
		$smnames = Array();

		//All rates will be stored in this array
		$rates = Array();

		//Setting initial value for money type
		$noncash = true;

		//Looping trough all rates rows
		$start = strpos($cnt, '>');
		while(is_int($start) && $row = findData($cnt, '<TR>', '</TR>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Deleting variable type
			unset($type);

			//cell number
			$i = 0;

			//Setting position to search from first char
			$rstart = 0;
			while(is_int($rstart) && $cell = findData($rcnt, '>', '</TD>', $rstart, true)) {
				$rstart = $cell['end']+5;
				//saving cell content
				$cellcontent = trim(strip_tags($cell['data']));

				if($i == 0) {
					if(preg_match("/\d{1,2}\.\d{1,2}\.\d{4}/s", $cellcontent)) {
						//Setting row type 0 - symbol names
						$type = 0;
					}elseif(is_int(strpos($cellcontent, 'Покупка'))) {
						//Setting row type 1 - buy row
						$type = 1;
					}elseif(is_int(strpos($cellcontent, 'Продажа'))) {
						//Setting row type 2 - sell row
						$type = 2;
					}elseif(is_int(strpos($cellcontent, 'Курс ЦБ РФ'))) {
						break 2;
					}
				}

				if($i > 0 && $type == 0) {
					//saving to array
					$smnames[$i] = $cellcontent;
				}elseif($i > 0){

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