<?php
require_once(BASE_PATH.'/base_parser.php');

class RTSBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://rtsbank.ru/%D0%97%D0%B0%D0%B3%D1%80%D1%83%D0%B7%D0%BA%D0%B0/curr.php');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];
		
		// Remove all element attributes
		$data = preg_replace('/<([\w\d]+)[^>]*>/i', '<\\1>', $data);
		
		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, '</div>','</body>', 0, true, false);
		$cnt = $data['data'];
		
		$cnt = str_replace(array(
			'<img>', '<br>'
		), array(
			'<div>', '</div>'
		), $cnt);
		
		// Removing <tags>
		$cnt = preg_replace('/\s+/', '', strip_tags($cnt, '<div>')).'</div>';
		
		//Looping trough all rates rows
		$start = 0;
		while(is_int($start) && $row = findData($cnt, '<div>', '</div>', $start, true)) {
			$rcnt = $row['data'];
			$start = $row['end'];

			// Looking for currency symbol
			$rstart = 0;
			$symbol = substr($rcnt, 0, 3);
			
			// Looking for buying & selling rate
			$exp = explode('/', $rcnt);
			$exp[0] = str_replace($symbol, '', $exp[0]);
		
			// Buying rate
			$buyCash = $exp[0];
			// Selling rate
			$sellCash = $exp[1];
			
			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>
