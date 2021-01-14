<?php
require_once(BASE_PATH.'/base_parser.php');

class BelsocBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://belsocbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data, '<tr><td colspan="4"','</table>', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<tr[^>]+>/is", '<tr>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		$eurRates = Array();
		$start = stripos($cnt, '</td>');
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			if(is_int(stripos($data['data'], 'dollar'))) $symbol = 'USD';
			elseif(is_int(stripos($data['data'], 'euro'))) $symbol = 'EUR';
			else $symbol = '';

			if($symbol == 'USD') {
				//Looking for buy/sell rate (second HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				list($buyCash, $eurRate) = explode('<br>', trim(strip_tags($data['data'], '<br>')));
				$buyCash = $this->CheckRate(trim($buyCash));
				$eurRates['buy'] = $this->CheckRate(trim($eurRate));
			}elseif($symbol == 'EUR')
				$buyCash = $eurRates['buy'];

			if($symbol == 'USD') {
				//Looking for buy/sell rate (second HTML table cell)
				$rstart = $data['end'];
				$data = findData($rcnt, '<td>', '</td>', $rstart, true);
				list($sellCash, $eurRate) = explode('<br>', trim(strip_tags($data['data'], '<br>')));
				$sellCash = $this->CheckRate(trim($sellCash));
				$eurRates['sell'] = $this->CheckRate(trim($eurRate));
			}elseif($symbol == 'EUR')
				$sellCash = $eurRates['sell'];

			if($symbol == 'USD' || $symbol == 'EUR')
				$this->AddRate(
					$this->GetSymbolID($symbol), //Symbol ID.
					1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
					$buyCash, //Cash foreign currency buy rate
					$sellCash, //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
		}
	}
}
?>