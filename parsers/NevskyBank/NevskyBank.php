<?php
require_once(BASE_PATH.'/base_parser.php');

class NevskyBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.nevskybank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '<table id="calcul">', 'Калькулятор', 0, true);
		$cnt = trim($data['data']);

		//Unifying <tags>
		$cnt = preg_replace("/<strong[^>]+>/is", '<strong>', $cnt);
		$cnt = preg_replace("/<td[^>]+>/is", '<td>', $cnt);

		//Variable for deal type
		$dtype = '';

		//default quantity
		$qty = 1;

		//Array for rates
		$rates = Array();

		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			$rstart = 0;
			while(is_int($rstart) && $cell = findData($rcnt, '<td>', '</td>', $rstart, true)) {
				$ccnt = $cell['data'];
				$rstart = $cell['end'];

				/*
					If function 'if' analysed $rcnt for 'Продажа' and found it. 
					Then function 'stripos' found their position and function 'is_int' is true, $dtype = 'sell'.
				*/
				if (is_int(stripos($ccnt,'Продажа'))){
					$dtype = 'Sell';
				} elseif (is_int(stripos($ccnt,'Покупка'))){
					$dtype = 'Buy';
				} elseif (preg_match("/Свыше (\d+) единиц/is", $ccnt, $regs)){
					$qty = $regs[1];
					break;
				}

				//Finding currency symbol and make a function cyclic
				$cstart = stripos($ccnt, '<br>');
				while(is_int($cstart) && $data = findData($ccnt, '<br>', '<strong>', $cstart, true)) {
					$cstart = $data['end'];
					$symbol = trim(strip_tags($data['data']));

					//Looking for rate
					$data = findData($ccnt, '<strong>', '</strong>', $cstart, true);
					$rate = $this->CheckRate(trim(strip_tags($data['data'])));
					$rates[$symbol][$qty][$dtype] = $rate;
				}
			}
		}

		foreach ($rates as $symbol => $qtys) {
			foreach ($qtys as $qty => $cash) {
				//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
				$this->AddRate(
					$this->GetSymbolID($symbol), //Symbol ID
					$qty, //Minimal deal amount. Not mentioned on the page. By default this value should be 1
					$cash['Buy'], //Cash foreign currency buy rate
					$cash['Sell'], //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
			}
		}
	}
}
?>