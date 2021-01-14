<?php
require_once(BASE_PATH.'/base_parser.php');

class CCB extends baseParser {

	public function Init() {
		$this->fetchUrl('Rates', 'http://www.ccb.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];
	
		$data = iconv("windows-1251","UTF-8",$data);	
		$data = findData($data,'Валюта','<td colspan="4"', 0, true);
		$cnt = trim($data['data']);
		
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>','</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;
			$pattern = '/([A-Za-z]{3}) \(([0-9]+)\)/';

			$data = findData($rcnt, '>', '</td>', $rstart, true);
			if (preg_match($pattern, $data['data'], $matches)) {
				$symbol = $matches[1];
				$divide = $matches[2];
			}
			else
				$symbol = trim(strip_tags($data['data']));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
	
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			if (!isset($divide))
				$divide = 1;
			
			$this->AddRate($this->GetSymbolID($symbol), $divide, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
