<?php

class BankFiK extends baseParser{
	public function Init (){
		/*
			Settings initial URL or multiple URLs
		First parameter is method name without prefix "Parse".
		Second parameter is URL to fetch.
		To set multiple URLs use same method with different URLs.
		*/
		$this->fetchUrl ('rates', 'http://www.fc.kiev.ua/currency/');
	}

	public function ParseRates ($mdata=array()){
		/*
		We have only one URL that's why $mdate Array has only one element.
		shifting that element to variable $page.
		*/
		$page = array_shift($mdata);
		$pageCnt = $page ['pc_content'];

		//Looking for parts of the page containing foreign rates and assigning it's content to variable $data
		$needPart = findData($pageCnt,'<td>Продажа</td>', '</tbody>', 0, true );

		// getting cell value from array
		$cnt = trim(strip_tags($needPart['data'], '<tr><td>'));  
		//removing useless spaces
		$cnt = preg_replace("/>\s+/is", '>', $cnt);
		$cnt = preg_replace("/\s+</is", '<', $cnt);
		//removing starting tag
		$cnt = str_ireplace('<td>', '', $cnt);
		//removing starting tag with class GREEN
		$cnt = str_ireplace('<td class="green">', '', $cnt);

		$start = 0;
		//Looping through all rates rows
		while(is_int($start) && $rowData = findData($cnt, '<tr>', '</tr>', $start, true)){
			//Changing position from witch to search
			$start = $rowData['end'];
			//Assisting row content to variable $rcnt
			$rcnt = $rowData['data'];

			//Default amount for cell Qty
			$qty = 1;

			//Separating row in to parts by </td>
			list($symbol, $longSymb, $qty, $buyCash, $sellCash) = explode('</td>', $rcnt);

			//preparing buy rate to be acceptable by database, also geting rate
			$buyCash = $this->CheckRate($buyCash,$qty);
			$sellCash = $this->CheckRate($sellCash,$qty);
			//There is no None-cash
			$buy = 0;
			$sell = 0;

			$this->AddRate(
				//Symbol ID
				$this->GetSymbolID($symbol),
				//Minimal deal amount
				$qty,
				//Cash foreign currency buy rate
				$buyCash,
				//Cash foreign currency sell rate
				$sellCash,
				//Non-Cash foreign currency buy rate
				$buy,
				//Non-Cash foreign currency sell rate
				$sell
			);
		}
	}
}
?>