<?php

class UpBank extends baseParser {
PUBLIC FUNCTION Init () {
		/*
			Setting initial URL or multiple URLs
			First parameter is method name without prefix "Parse"
			Second parameter is URL to fetch.
			To set multiple URLs use same method with different URLs. 
		*/
		$this->fetchURL('Rates', 'http://upb.ua/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting that element to variable $page.
		*/
		$page = array_shift($mdata);
		$pagecnt = $page['pc_content'];

		// Looking for part of the page containing foreign rates and assigning it's content to variable $needPart
		$needPart = findData($pagecnt, 'class="courses">', '<td colspan=3', 0, true);
		$cnt = trim(strip_tags($needPart['data'], '<tr><td>'));

		//Unifying <tags> 
		$cnt = preg_replace("/<(\w+) [^>]+>/is", '<\\1>', $cnt);

		//Removing emty cells
		$cnt = str_ireplace('&nbsp;', '', $cnt);

		//Removing useless spaces
		$cnt = preg_replace("/>\s+/is", '>', $cnt);
		$cnt = preg_replace("/\s+</is", '<', $cnt);
		//Remove cell start tag
		$cnt = str_ireplace('<td>', '', $cnt);

		$cnt = str_ireplace('*', '', $cnt);
		$cnt = str_ireplace('RUR', 'RUB', $cnt);

		//Setting cycle position to zero
		$start = stripos($cnt, '</tr>');
		//Looping troght all rates row
		while (is_int($start) && $rowData = findData($cnt, '<tr>', '</tr>', $start, true)) {
			// Changing position from witch to search
			$start = $rowData['end'];
			//Assigning row content to variable $rcnt
			$rcnt = $rowData['data'];

			//Minimal deal amount by default
			$qty = 1;

			//Separating row in to parts by '</td>'
			list($symbol, $buyCash, $sellCash) = explode('</td>', $rcnt);
			//Preparing buy rate to a be acceptable by database, also getting rate for one unit
			$buyCash = $this->CheckRate($buyCash, $qty);
			//Preparing sell rate to a be acceptable by database, also getting rate for one unit
			$sellCash = $this->CheckRate($sellCash, $qty);
			//There is no non-cash buy rate
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