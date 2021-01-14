<?php

class UkrinBank extends baseParser{
	public function Init (){
		/*
			Settings initial URL or multiple URLs
		First parameter is method name without prefix "Parse".
		Second parameter is URL to fetch.
		To set multiple URLs use same method with different URLs.
		*/
		$this->fetchUrl ('rates', 'http://www.ukrinbank.com/');
	}

	public function ParseRates ($mdata=array()){
		/*
		We have only one URL that's why $mdate Array has only one element.
		shifting that element to variable $page.
		*/
		$page = array_shift($mdata);
		$pageCnt = $page ['pc_content'];

		//Looking for parts of the page containing foreign rates and assigning it's content to variable $data
		$needPart = findData($pageCnt, '<tr><td colspan="4" class="dev"></td></tr>', '</table>', 0, true );
		//Getting cell value from array
		$cnt = trim(strip_tags($needPart['data'], '<tr><td>'));  

		//Unifying <tags>
		$cnt = preg_replace("/<(\w+) [^>]+>/is",'<\\1>', $cnt);

		//Removing useless spaces
		$cnt = preg_replace("/>\s+/is", '>', $cnt);
		$cnt = preg_replace("/\s+</is", '<', $cnt);
		//Removing empty cells
		$cnt = str_ireplace('<td></td>', '', $cnt);
		//Removing starting tag
		$cnt = str_ireplace('<td>', '', $cnt);
		//Changing RUR to RUB
		$cnt = str_ireplace('RUR', 'RUB', $cnt);
		//Removing 
		$cnt = preg_replace("/\s+/is", '', $cnt);

		//Setting cycle position to zero
		$start = 0;
		//Looping through all rates rows
		while(is_int($start) && $rowData = findData($cnt, '<tr>', '</tr>', $start, true)){
			//Changing position from witch to search
			$start = $rowData['end'];
			//Assisting row content to variable $rcnt
			$rcnt = $rowData['data'];

			//Minimal deal amount by default
			$qty = 1;
			//Separating row in to parts by </td>
			list($symbol, $buyCash, $sellCash) = explode('</td>', $rcnt);

			//Setting custom quantity
			if($symbol == 'RUB')
				$qty = 10;
			else
				$qty = 100;

			//preparing buy rate to be acceptable by database, also geting rate
			$buyCash = $this->CheckRate($buyCash,$qty);
			//preparing buy rate to be acceptable by database, also geting rate
			$sellCash = $this->CheckRate($sellCash,$qty);
			//There is no None-cash buy rate
			$buy = 0;
			//There is no None-cash sell rate
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