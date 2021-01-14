<?php
require_once(BASE_PATH.'/base_parser.php');

class RosCredit extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://new.roscredit.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data, '<div class="rates">','<div class="contentShadow"></div>', 0, true);
		$cnt = trim($data['data']);
		$cnt = substr($cnt, stripos($cnt, '<div class="blocks">'));

		//Unifying <tags>
		$cnt = preg_replace("/<p[^>]+>/is", '<p>', $cnt);
		$cnt = preg_replace("/<div[^>]+>/is", '<div>', $cnt);

		//Array will hold all rates & symbols
		$rates = Array();
		$symbols = Array();

		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<div>', '</div>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			if(is_int(strpos($rcnt, 'Валюта'))) $type = 0;
			elseif(is_int(strpos($rcnt, 'Покупка')))  $type = 1;
			elseif(is_int(strpos($rcnt, 'Продажа')))  $type = 2;

			$i = 0;
			$rstart = stripos($rcnt, '</p>');
			while(is_int($rstart) && $cell = findData($rcnt, '<p>', '</p>', $rstart, true)) {
				$rstart = $cell['end'];

				if($type == 0)
					$symbols[$i++] = trim(strip_tags($cell['data']));
				elseif($type == 1)
					$rates[$symbols[$i++]]['buyCash'] = $this->CheckRate(trim(strip_tags($cell['data'])));
				elseif($type == 2)
					$rates[$symbols[$i++]]['sellCash'] = $this->CheckRate(trim(strip_tags($cell['data'])));
			}
		}

		if(is_array($rates) && count($rates) > 0)
			foreach($rates as $symbol => $rate) {
				$this->AddRate(
					$this->GetSymbolID($symbol), //Symbol ID.
					1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
					$rate['buyCash'], //Cash foreign currency buy rate
					$rate['sellCash'], //Cash foreign currency sell rate
					0, //Non-Cash foreign currency buy rate
					0 //Non-Cash foreign currency sell rate
				);
			}
	}
}
?>