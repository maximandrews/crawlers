<?php
require_once(BASE_PATH.'/base_parser.php');

class Swedbank extends baseParser {
	private $tr = Array('ē'=>'e','ū'=>'u','ī'=>'i','ā'=>'a','š'=>'s','ģ'=>'g','ķ'=>'k','ļ'=>'l','ž'=>'z','č'=>'c','ņ'=>'n');

	public function Init() {
		$this->fetchUrl('Rates', 'https://ib.swedbank.lv/private/d2d/payments/rates/currency?language=LAT');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];
		//echo $data; exit;

		$start = stripos($data, '<th class="dtTotal"></th>');
		$data = findData($data, '</tr>','</table>', $start, true);
		$cnt = trim($data['data']);
		$cnt = str_replace(' class="dtCell"', '', $cnt);
		$cnt = str_replace(' class="odd"', '', $cnt);

		$start = 0;
		while(is_int($start) && $data = findData($cnt, '<td style="text-align: left;">', '&nbsp;', $start, true)) {
			$symbol = trim(strip_tags($data['data']));
			$start = $data['end'];
			$data = findData($cnt, '<td>', '</td>', $start, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));
			$start = $data['end'];
			$data = findData($cnt, '<td>', '</td>', $start, true);
			$buy = $this->CheckRate(trim(strip_tags($data['data'])));
			$start = $data['end'];
			$data = findData($cnt, '<td>', '</td>', $start, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			$start = $data['end'];
			$data = findData($cnt, '<td>', '</td>', $start, true);
			$sell = $this->CheckRate(trim(strip_tags($data['data'])), 1);
			$start = $data['end'];

			//echo $symbol.": $buyCash, $sellCash, $buy, $sell;<br>\n";
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, $buy, $sell);
		}
	}
}

?>