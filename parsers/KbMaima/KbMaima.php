<?php

require_once(BASE_PATH.'/base_parser.php');

class KbMaima extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.kbmaima.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data, '<table width="100%" >','</table>', 0, true);
		$cnt = trim($data['data']);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, 'alt="', '"/>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			
			//Looking for buying rate (second HTML table cell)
			$data = findData($rcnt, '><strong>', '</strong></div></td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				0, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}
?>