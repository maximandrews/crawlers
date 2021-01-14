<?php
require_once(BASE_PATH.'/base_parser.php');

class EMBank extends baseParser {
	public function Init() {
		/*
			Setting initial URL, or multiple URLs.
			First parameter is function name without prefix 'Parse'.
			Second parameter is URL to fetch.
			To set multiple URLs use same function name with different URLs
		*/
		$this->fetchUrl('Rates', 'http://www.emb.ru/');
	}

	public function ParseRates($mdata=Array()) {
		/*
			We have only one URL that's why $mdata Array has only one element.
			Shifting this element to variable $page.
		*/
		$page = array_shift($mdata);
		$data = $page['pc_content'];

		//Looking for part of the page where we have foreign rates and assigning it's content to variable $cnt		
		$data = findData($data, '<div class="curr-row cur-title">','<div class="curr-links">', 0, true);
		$cnt = iconv('windows-1251', 'UTF-8', trim($data['data']));


		//Looping trough all rates rows
		$start = strpos($cnt, '<div class="curr-row">');
		while(is_int($start) && $row = findData($cnt, '<div class="curr-name">', '<div class="curr-row', $start, true)) {
			$rcnt = strip_tags($row['data'], '<span><small>');
			$start = $row['end'];

			//Setting $amount to a default value
			$amount = 1;

			//Looking for currency symbol (first HTML table cell)
			$rstart = 0;
			$data = findData($rcnt, '<span>', '<', $rstart, true);
			$symbol = trim(strip_tags($data['data']));

			$data = findData($rcnt, '<span>', '</span>', $rstart, true);
			$rstart = $data['end'];

			if ($data = findData($data['data'], '<small>', '</small>', 0, true)) {
				if(preg_match("/\d+/", $data['data'], $regs))
					$amount = $regs[0];
			}

			//Looking for buying rate (third HTML table cell)
			$data = findData($rcnt, '<span>', '</span>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])), $amount);

			//Looking for selling rate (fourth HTML table cell)
			$rstart = $data['end'];
			$data = findData($rcnt, '<span>', '</span>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])), $amount);

			//Saving rate. There is no Non-cash rates on page. Passing zeros for Non-Cash values
			$this->AddRate(
				$this->GetSymbolID($symbol), //Symbol ID.
				$amount, //Minimal deal amount. Mentioned only by some currencies. By default this value should be 1.
				$buyCash, //Cash foreign currency buy rate
				$sellCash, //Cash foreign currency sell rate
				0, //Non-Cash foreign currency buy rate
				0 //Non-Cash foreign currency sell rate
			);
		}
	}
}

?>