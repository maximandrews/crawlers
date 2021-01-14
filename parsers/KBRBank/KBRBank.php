<?php
require_once(BASE_PATH.'/base_parser.php');

class KBRBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.kbrbank.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element ro variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		// Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt
		$data = findData($data, 'class="currency-block"','<div class="bank-block">', 0, true, false);
		$cnt = $data['data'];

		// Remove <h4> messages
		$cnt = preg_replace('!<h4>.*?</h4>!is', '', $cnt);
		// Remove <h3> messages
		$cnt = preg_replace('!<h3>.*?</h3>!is', '', $cnt);
		// Remove <h2> messages
		$cnt = preg_replace('!<h2>.*?</h2>!is', '', $cnt);
		// Remove <div class="heading">
		$cnt = preg_replace('!<div class="heading"><div>.*?</div></div>!is', '', $cnt);
		
		// Remove &nbsp; &mdash; and some div classes
		$cnt = str_replace(array(
			'&nbsp;', '&mdash;', '<div class="clear"></div>', '<div></div>'
		), '', $cnt);
		
		// Get rates
		$data = findData($cnt, '<li>','</li>', 0, true, false);
		$cnt  = $data['data'];
		
		// Remove all attributes
		$cnt = preg_replace('/<([\w\d]+)[^>]*>/i', '<\\1>', $cnt);
		
		// Remove white spaces
		$cnt = preg_replace('/[\s+]/', '', $cnt);
		
		// Replace double div's with single
		$cnt = str_replace('<div><div>', '<div>', $cnt);
		$cnt = str_replace('</div></div>', '</div>', $cnt);
		// new rates array
		$rates = array();
		
		// add rates to array
		$start 	= 0;
		$i 		= 0;
		while(is_int($start) && $row = findData($cnt, '<div>', '</div>', $start, true)) {
			$rcnt  = $row['data'];
			$start = $row['end'];

			if($i%2 == 0)
			{
				$rates['usd'][] = $rcnt;
			}
			else {
				$rates['eur'][] = $rcnt;
			}

			$i++;
		}
		
		// Parse rates
		foreach($rates as $symbol => $data)
		{
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				1, //Minimal deal amount. Not mentioned on the page. By default this value should be 1.
				$data[0], //Cash foreign currency buy rate
				$data[1], //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>