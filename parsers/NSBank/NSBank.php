<?php
require_once(BASE_PATH.'/base_parser.php');

class NSBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.nsbank.ru/individuals/20/exchange_rates/?summ=');
		$this->fetchUrl('Rates', 'http://www.nsbank.ru/individuals/20/exchange_rates/?summ=1000');
		$this->fetchUrl('Rates', 'http://www.nsbank.ru/individuals/20/exchange_rates/?summ=10000');
		$this->fetchUrl('Rates', 'http://www.nsbank.ru/individuals/20/exchange_rates/?summ=20000');
	}

	public function ParseRates($mdata=Array()) {

		//All rates will be stored in this array
		$rates = Array();

		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		foreach($mdata as $page) {
			$data = $page['pc_content'];
	
			//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
			$data = findData($data, 'Головной офис','</table>', 0, true);
			$cnt = trim($data['data']);

			if(preg_match("/summ=(\d+)$/is", $page['pc_url'], $regs))
				$qty = $regs[1];
			else
				$qty = 1;

			// Modifying tags for simplifying search
			$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);
			$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);

			//Preparing array for currency symbol names
			$smnames = Array();

			//Looping trough all rates rows
			$start = 0;
			while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
				$rcnt = $row['data'];
				$start = $row['end'];

				$i = 0; //cell number
				$rstart = 0;//Setting position to search from first char

				while(is_int($rstart) && $cell = findData($rcnt, '<td>', '</td>', $rstart, true)) {
					$rstart = $cell['end'];
					//saving cell content
					$cellcontent = trim(strip_tags($cell['data']));
					if($i == 0) {
						if(is_int(strpos($cellcontent, 'покупка'))) {
							//Setting buy row
							$index = 'buy';
						}elseif(is_int(strpos($cellcontent, 'продажа'))) {
							//Setting sell row
							$index = 'sell';
						}else
							$index = '';
					}

					if($i > 0 && empty($index)) {
						//saving to array
						$smnames[$i] = $cellcontent;
					}elseif($i > 0 && !empty($index)){
						if(!isset($rates[$smnames[$i]][$qty])) $rates[$smnames[$i]][$qty] = Array();

						$rates[$smnames[$i]][$qty][$index] = $this->CheckRate($cellcontent);
					}
					//Cell # increment
					$i++;
				}
			}
		}

		//ksort($rates);

		foreach($rates as $symbol => $qtys) {
			//ksort($qtys);
			foreach($qtys as $qty => $rate) {
				//Saving rate. There is only cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
					$this->GetSymbolID($symbol), //Symbol ID.
					$qty, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
					$rate['buy'], //Cash foreign currency buy rate
					$rate['sell'], //Cash foreign currency sell rate
					0,//Cash foreign currency buy rate
					0 //Cash foreign currency sell rate
				);
			}
		}
	}
}

?>