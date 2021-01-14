<?php
require_once(BASE_PATH.'/base_parser.php');

class AVBBank extends baseParser {

	public function Init() {
		$this->fetchUrl('Rates', 'http://avbbank.ru/all_currencies/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		$data = findData($data,'<table>','</table>', 0, true);
		$cnt = trim($data['data']);
		$cnt = str_replace(' align="center"', '', $cnt);

		$cntSymb = findData($cnt,'</th>','</tr>', 0, true);
		$cntSymb = trim($cntSymb['data']);
		$cntBuy = findData($cnt,'Купить','Продать', 0, true);
		$cntBuy = trim($cntBuy['data']);
		$cntSell = findData($cnt,'Продать','ЦБ РФ', 0, true);
		$cntSell = trim($cntSell['data']);

		$startSymb = 0;
		$startBuy = 0;
		$startSell = 0;
		while(is_int($startSymb) && $row = findData($cntSymb, '<th>','</th>', $startSymb, true)) {
			$rcnt = $row['data'];
			$startSymb = $row['end'];
			$rstartSymb = 0;
			$data = findData($rcnt, '<th>', '</th>', $rstartSymb, true);
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
