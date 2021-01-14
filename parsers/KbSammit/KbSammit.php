<?php
require_once(BASE_PATH.'/base_parser.php');

class KbSammit extends baseParser {
	public function Init() {
		$this->fetchUrl('Rates', 'http://www.kbsammit.ru/');
	}

	public function ParseRates($mdata=Array()) {
		$page = array_shift($mdata);
		$data = $page['pc_content'];	

		$data = findData($data, 'продажа</span>','</div>', 0, true);
		$cnt = trim($data['data']);

		$start = 0;
		$off=0;
		$pattern = '/\/[^\/]([a-z]+)_[0-9]+./';
		while(is_int($start) && $row = findData($cnt, '<tr>', '</tr>', $start, true)) {		
			$rcnt = $row['data'];
			$start = $row['end'];
			
			$rstart = 0;
			$data = findData($rcnt, '<td', '</td>', $rstart, true);
			preg_match($pattern,$cnt,$matches,PREG_OFFSET_CAPTURE,$off);
			$off = $matches[0][1] + strlen($matches[0][0]);
			$qty=1;
               switch($matches[1][0]) {
				case 'uro':
					$symbol = 'EUR';
					break;
				case 'ollar':
					$symbol = 'USD';
					break;
				case 'na':
					$symbol = 'JPY';
		               $qty	= 100;
					break;
				case 'an':
					$symbol = 'CNY';
					break;
			}

			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$buyCash = $this->CheckRate(trim(strip_tags($data['data'])));

			$rstart = $data['end']+5;
			$data = findData($rcnt, '>', '</td>', $rstart, true);
			$sellCash = $this->CheckRate(trim(strip_tags($data['data'])));
			
			$this->AddRate($this->GetSymbolID($symbol), $qty, $buyCash, $sellCash, 0, 0);
		}
	}
}

?>
