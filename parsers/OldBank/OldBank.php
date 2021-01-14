<?php

class OldBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL or multiple URLs.
			First parameter is method name without prefix "Parse".
			Second parameter is URL to fetch.
			To set multiple URLs use name method with different URLs. 
		*/
		$this->fetchUrl('Rates', 'http://www.oldbank.com/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting that element to variable $page.
		*/
		$page = array_shift($mdata);
		$pageCnt = $page['pc_content'];

		//Looking for part of the page containing foreign rates and assigning it's content to variable $data
		$needPart = findData($pageCnt,'НБУ</th></tr>','</table>', 0, true);
		$cnt = trim(strip_tags($needPart['data'], '<tr><td>'));

		//Unifying <tags>
		$cnt = preg_replace("/<(\w+) [^>]+>/is",'<\\1>', $cnt);
		//Removing useless spaces
		$cnt = preg_replace("/>\s+/is",'>', $cnt);
		$cnt = preg_replace("/\s+</is",'<', $cnt);
		//Removing empty cells
		$cnt = str_ireplace('<td></td>', '', $cnt);
		//Removing cell start tag
		$cnt = str_ireplace('<td>', '', $cnt);

		//Setting cycle position to zero
		$start = 0;

		//Looping through all rates rows
		while(is_int($start) && $rowData = findData($cnt, '<tr>', '</tr>', $start, true)) {
			//Changing position from which to search
			$start = $rowData['end'];
			//Assigning row content to variable $rcnt
			$rcnt = $rowData['data'];

			//Minimal deal amount by default
			$qty = 100;

			//Separating row into parts by '</td>'
			list($symbol, $buyCash, $sellCash) = explode('</td>', $rcnt);

			if($symbol == 'RUB')
				$qty = 10;
			//Preparing buy rate to be acceptable by database, also getting rate for one unit
			$buyCash = $this->CheckRate($buyCash, $qty);
			//Preparing sell rate to be acceptable by database, also getting rate for one unit 
			$sellCash = $this->CheckRate($sellCash, $qty);
			//There is no non-cash by rate
			$buy = 0;
			//There is no non-cash sell rate
			$sell = 0;

			$this->AddRate(
				//symbol ID
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