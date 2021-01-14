<?php
require_once(BASE_PATH.'/base_parser.php');

class PrioVneshneTorgBank extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.priovtb.com/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = iconv("windows-1251","UTF-8", $page['pc_content']);	

		$data = findData($data, 'продажа</td>','</table>', 0, true);
		$cnt = trim($data['data']);
		$cnt = str_replace('&nbsp;/&nbsp;RUB', '', $cnt);

		$start = 0;
		$i = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			if ($i>1) break;
			$i++; 

			$rcnt = $row['data'];
			$start = $row['end'];
			
			$rstart = 0;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));
						

			$rstart = $data['end']+5;
			$data = findData($rcnt, 'value="', '"', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, 'value="', '"', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
