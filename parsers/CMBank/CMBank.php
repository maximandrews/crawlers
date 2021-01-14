<?php
require_once(BASE_PATH.'/base_parser.php');

class CMBank extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.cmbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = iconv("windows-1251","UTF-8", $page['pc_content']);

		$data1 = findData($data, 'class="TableCourse">','от 5000', 0, true);
		$cnt1 = trim($data1['data']);
		$data2 = findData($data, 'от 5000','</table>', 0, true);
		$cnt2 = trim($data2['data']);
	
		$start = 0;
		while(is_int($start) && $row = findData($cnt1, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;

			$data1 = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data1['data']));
			
			$rstart = $data1['end']+5;
			$data1 = findData($rcnt, '>', '</td>', $rstart, true);			
			$buyCash = $this->CheckRate(trim(strip_tags($data1['data'])));

			$rstart = $data1['end']+5;
			$data1 = findData($rcnt, '>', '</td>', $rstart, true);
			
			$rstart = $data1['end']+5;
			$data1 = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data1['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
		
		$start = 0;
		while(is_int($start) && $row = findData($cnt2, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;

			$data2 = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data2['data']));
			
			$rstart = $data2['end']+5;
			$data2 = findData($rcnt, '>', '</td>', $rstart, true);			
			$buyCash = $this->CheckRate(trim(strip_tags($data2['data'])));

			$rstart = $data2['end']+5;
			$data2 = findData($rcnt, '>', '</td>', $rstart, true);
			
			$rstart = $data2['end']+5;
			$data2 = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data2['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), 5000, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
