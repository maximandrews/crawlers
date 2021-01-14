<?php
require_once(BASE_PATH.'/base_parser.php');

class UnicreditBank extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.unicreditbank.ru/rus/reg/moscow/personal/rko/cash/currency_rates/obm.wbp');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data, '<table class="conTableGrey">','</table>', 0, true);
		$data = trim($data['data']);
		$data = findData($data, '<tbody>','</tbody>', 0, true);
		$cnt = trim($data['data']);
		
		$cnt = str_replace(' class="even"', '', $cnt);
		$cnt = str_replace('<th>', '', $cnt);
		$cnt = iconv("windows-1251","UTF-8",$cnt);

		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];
			$rstart = 0;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$divide = trim(strip_tags($data['data']));

			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), $divide, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
