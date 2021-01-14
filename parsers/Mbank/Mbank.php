<?php

class Mbank extends baseParser {
	public function Init() {
		/*
			Setting initial URL or multiple URL`s
			first parameter is method name without prefix "Parse"
			Scond parameter is URL to fetch.
			To set multiple URL`s use same method with different URLS
		*/
		$this->fetchURL('Rates','http://www.mbank.com.ua/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that why $mdata Array has only one element.
			Shifting that element to variable $page.
		*/
		$page = array_shift($mdata);
		$pageCnt = $page['pc_content'];

		//Looking for part of the page containing foreign rates and assigning it`s content to variable $data
		$needPart = findData($pageCnt, ' <td class=\'rate_header\' align=\'right\' valign=\'top\'>', '</table>', 0, true);
		$cnt = trim(strip_tags($needPart['data'], '<tr><td>'));

		//Unifying <tags>
		$cnt = preg_replace("/<(\w+) [^>]+>/is",'<\\1>',$cnt);

		//Removing $nbsp; free space
		$cnt = str_ireplace('&nbsp;', '',$cnt);

		//Removing useless spaces
		$cnt = preg_replace("/>\s+/is",'>',$cnt);
		$cnt = preg_replace("/\s+</is",'<',$cnt);

		//Removing empty cell 
		$cnt = str_ireplace('<td></td>', '', $cnt);
		//Removing empty cell start tag
		$cnt = str_ireplace('<td>', '', $cnt);

		//Setting cycle position to zero //stripos($cnt,'</tr>');//default 0;
		$start = 0;

		//Loop through all rates row
		while(is_int($start) && $rowData = findData($cnt,'<tr>', '</tr>', $start, true)) {
			//Changing position from witch to serch
			$start = $rowData['end'];
			//Assign row content to variable $rcnt
			$rcnt = $rowData['data'];

			//$symbol RUB default quantity
			$qty = 1;

			//Seperateing row in to parts
			list($symbol, $buyCash, $sellCash) = explode('</td>', $rcnt);

			//If symbol is RUB, then change quantity to 10
			if ($symbol == 'RUB') 
				$qty = 10;

			//Preparing buy rate to be acceptable by database, also getting rate for one unit
			$buyCash = $this->CheckRate($buyCash, $qty);
			//Preparing sell rate to be acceptable by database, also getting rate for one unit
			$sellCash = $this->CheckRate($sellCash, $qty);
			//There is no non-cash buy rate
			$buy = 0;
			$sell = 0;

			$this->AddRate(
				//Symbol ID
				$this->GetSymbolID($symbol),
				//Minimal deal amount
				$qty,
				//Cash foreign currency buy rate
				$buyCash,
				//Non-Cash foreign currency sell rate
				$sellCash,
				// foreign currency buy rate
				$buy,
				//Non-Cash foreign currency sell rate
				$sell
			);
		}
	}
}
?>