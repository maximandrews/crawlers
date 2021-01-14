<?php
require_once(BASE_PATH.'/base_parser.php');

class Logosbank extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.logosbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];
		//echo $data; exit;

		$data = findData($data, '<table class=kurs>','</table>', 0, true);
		$cnt = trim($data['data']);
		//var_dump($cnt);exit;

		$cnt = str_replace(' class="dtCell"', '', $cnt);
		$cnt = str_replace(' class="odd"', '', $cnt);

		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			$rstart = 0;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);

			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));


			//echo "$symbol: $buyCash $sellCash\n";//$buy, $sell;<br>\n";
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
