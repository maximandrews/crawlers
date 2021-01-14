<?php
require_once(BASE_PATH.'/base_parser.php');

class UralPrivatBank extends baseParser {

	public function Init() {
		$this->fetchUrl('Rates', 'http://upb.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];
	
		$data = findData($data,'<thead>','</tbody>', 0, true);
		$cnt = trim($data['data']);
		$cnt = str_replace('&nbsp;', '', $cnt);	
 	

		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>','</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;

			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '<span', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '<td>', '<span', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
