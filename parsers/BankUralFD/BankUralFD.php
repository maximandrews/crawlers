<?php
require_once(BASE_PATH.'/base_parser.php');

class BankUralFD extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.uralfd.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = iconv("windows-1251","UTF-8", $page['pc_content']);	

		$data = findData($data, 'Продажа</th>','</table>', 0, true);
		$cnt = trim($data['data']);

		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			
			$rstart = 0;
			$data = findData($rcnt, '<th>', '</th>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
				

			$rstart = $data['end']+5;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '<td>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
