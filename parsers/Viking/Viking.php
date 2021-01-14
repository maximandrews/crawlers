<?php
require_once(BASE_PATH.'/base_parser.php');

class Viking extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://viking.spb.ru/Viking_new.nsf/%28sitetree%29/890139B4D5DE0F49C325737C00430F74?OpenDocument');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data, 'table class="curr_exch">','</table>', 0, true);
		$cnt = trim($data['data']);

		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;

			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);			
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
