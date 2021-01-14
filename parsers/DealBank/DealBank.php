<?php
require_once(BASE_PATH.'/base_parser.php');

class DealBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.deal-bank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<div class="content-v">','<div style="padding: 10px 0px 0px 0px; font-style:italic;">', 0, true);
		$cnt = trim($data['data']);

		//Changing encording
		$cnt = iconv('windows-1251','UTF-8',$cnt);

		//Unifying <tags>
		$cnt = preg_replace("/<div[^>]+>/is", '<div>', $cnt);
		$cnt = preg_replace("/<br[^>]+>/is", '<br/>', $cnt);

		//Setting default value for variable $j;
		$j = 0;

		//Looping trough all rates rows
		//Position of start of looking for currency name
		$start = strpos($cnt, '<div');
		$rates = Array();
		while(is_int($start) && $row = findData($cnt, '<div', '/div>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$i = 0;
			$rstart = 0;

			//Looking for any data in HTML table subcells)
			while(is_int($rstart) && $data = findData($rcnt, '>', '<', $rstart, true)){
				$rstart = $data['end'];

				switch ($j) {
					case 0:
						break;
					case 1:
						if ($i > 1) {
						if(!isset($rates['symbol'])) $rates['symbol'] = Array();
						$rates['symbol'][$i-2] = trim(strip_tags($data['data']));
					}
						Break;
					case 2:
						break;
					case 3:
						break;
					case 4:
						$rates['usdbuy'] = $this->CheckRate(trim(strip_tags($data['data'])));
						Break;
					case 5:
						$rates['usdsell'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 6:
						$rates['eurbuy'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
					case 7:
						$rates['eursell'] = $this->CheckRate(trim(strip_tags($data['data'])));
						break;
				}

				//Incrementing variable $i
				$i++;
			}

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values

			//Incrementing variable $j
			$j++;
		}

		$this->AddRate(
			$this->GetSymbolID($rates['symbol'][0]), //Symbol ID.
			1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
			$rates['usdbuy'], //Cash foreign currency buy rate
			$rates['usdsell'], //Cash foreign currency sell rate
			0, //Non-Cash foreign currency buy rate
			0 //Non-Cash foreign currency sell rate
		);

		$this->AddRate(
			$this->GetSymbolID($rates['symbol'][1]), //Symbol ID.
			1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
			$rates['eurbuy'], //Cash foreign currency buy rate
			$rates['eursell'], //Cash foreign currency sell rate
			0, //Non-Cash foreign currency buy rate
			0 //Non-Cash foreign currency sell rate
		);
	}
}
?>