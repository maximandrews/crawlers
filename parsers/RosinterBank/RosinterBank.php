<?php
require_once(BASE_PATH.'/base_parser.php');

class RosinterBank extends baseParser {

	public function Init() {
		$this->fetchUrl('Rates', 'http://www.rosinterbank.ru/obmen-valyuty/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data,'<table class="tbl_kurs">','</tbody>', 0, true);
		$cnt = trim($data['data']);
		$cnt = str_replace(' р.', '', $cnt);

		$cntSymb = findData($cnt,'<td class="td_operation">','</tr>', 0, true);
		$cntSymb = trim($cntSymb['data']);
		$cntBuy = findData($cnt,'Покупаем','Продаем', 0, true);
		$cntBuy = trim($cntBuy['data']);
		$cntSell = findData($cnt,'Продаем','Курс ЦБ', 0, true);
		$cntSell = trim($cntSell['data']);
		
		$startSymb = 0;
		$startBuy = 0;
		$startSell = 0;
		while(is_int($startSymb) && $row = findData($cntSymb, '<td>','</td>', $startSymb, true)) {
			$rcnt = $row['data'];
			$startSymb = $row['end'];
			$rstartSymb = 0;
			$data = findData($rcnt, '<td>', '</td>', $rstartSymb, true);
			$symbol = $rcnt;
			$rstartSymb = $data['end']+5;
			
			$rowBuy = findData($cntBuy, '>','</td>', $startBuy, true);
			$startBuy = $rowBuy['end'];
			$rstartBuy = 0;
			$data = findData($rcntBuy, '>', '</td>', $rstartBuy, true);
			$buyCash = $this->CheckRate(trim(strip_tags($rowBuy['data'])));
			$rstartBuy = $data['end']+5;

			$rowSell = findData($cntSell, '>','</td>', $startSell, true);
			$startSell = $rowSell['end'];
			$rstartSell = 0;
			$data = findData($rcntSell, '>', '</td>', $rstartSell, true);
			$sellCash = $this->CheckRate(trim(strip_tags($rowSell['data'])));
			$rstartSell = $data['end']+5;
			
			$this->AddRate($this->GetSymbolID($symbol), 1, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
