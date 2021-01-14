<?php

abstract class BaseParser {
	protected $fetchUrls = Array();
	protected $cards = Array(
		'americanexpress' => 1,	// American Express
		'mastercard' => 2,			// MasterCard
		'maestro' => 3,					// Maestro
		'cirrus' => 4,					// Cirrus
		'visa' => 5,						// Visa
		'visaelectron' => 6,		// Visa Electron
		'vpay' => 7,						// Visa V Pay
		'visaplus' => 8,				// Visa Plus
		'ec' => 9								// Electronic cash
	);
	protected $data = Array();
	private $symbols = Array();
	private $sms = Array();
	private $debug = 0;
	private $rates = Array();

	function __construct() {
		if(getenv('DEBUG')) echo 'class: '.get_class($this).'; ';
	}

	function getURLs() {
		if(!is_array($this->fetchUrls) || count($this->fetchUrls) == 0)
			return false;

		$all = $this->fetchUrls;
		$this->fetchUrls = Array();
		return $all;
	}

	protected function fetchUrl($method, $url) {
		if(!method_exists($this, 'Parse'.$method) || !preg_match("/^https?:\/\/[\d\w\.]+/is", is_array($url) && isset($url['url']) ? $url['url']:$url)) {
			echo 'Method Parse'.$method.' doesn\'t exists!'."\n";
			return false;
		}

		if(!is_array($this->fetchUrls)) $this->fetchUrls = Array();
		if(!isset($this->fetchUrls['Parse'.$method]))
			$this->fetchUrls['Parse'.$method] = Array();

		$this->fetchUrls['Parse'.$method][] = $url;
	}

	function Init() {
		//						parseRates
		//$this->fetchUrl('Rates', 'http://www.valutas.info');
	}

	protected function GetSymbolID($symbol, $image=NULL) {
		$symbol = trim($symbol);
		if(!preg_match("/^[A-Za-z]{3}$/is", $symbol))
			return false;

		if(!isset($this->symbols[$symbol])) {
			$sm = Array('sm_id' => count($this->symbols), 'sm_name' => strtoupper($symbol));
			$this->symbols[$symbol] = $sm;
			$this->sms[$sm['sm_id']] =& $this->symbols[$symbol];
		}

		return $this->symbols[$symbol]['sm_id'];
	}

	protected function CheckRate($rate, $qty=1) {
		$rate = !preg_match("/^[0-9]+((\.|,)[0-9]+){0,1}$/", $rate) ? 0:str_replace(',','.',$rate);

		if(!preg_match("/^[0-9]+$/", $qty)) $qty = 1;
		if($rate > 0 && $qty > 0) $rate /= $qty;

		return $rate;
	}

	protected function get($var) {
		return isset($this->data[$var]) ? $this->data[$var]:false;
	}

	protected function set($var, $obj) {
		$this->data[$var] = $obj;
	}

	protected function clean($var) {
		unset($this->data[$var]);
	}

	protected function AddRate($smID, $qty, $buyCash, $sellCash, $buy, $sell) {
		if(!is_int($smID)) return false;
		if(!isset($this->rates[$smID])) $this->rates[$smID] = Array();
		$this->rates[$smID][$qty] = Array('buyCash' => $buyCash, 'sellCash' => $sellCash, 'buy' => $buy, 'sell' => $sell);
	}

	public function makeOut() {
		$len = Array();
		
				$len['qty'] = strlen('Qty;');
				$len['buyCash'] = strlen('BuyCash;');
				$len['sellCash'] = strlen('SellCash;');
				$len['buy'] = strlen('Buy;');
				$len['sell'] = strlen('Sell;');
				
		foreach($this->rates as $key => $qrates) {
			foreach($qrates as $qty => $r) {
				$qtylen = strlen("$qty;");
				if($qtylen > $len['qty']) $len['qty'] = $qtylen;
				$buyCashlen = strlen($r['buyCash'].';');
				if($buyCashlen > $len['buyCash']) $len['buyCash'] = $buyCashlen;
				$sellCashlen = strlen($r['sellCash'].';');
				if($sellCashlen > $len['sellCash']) $len['sellCash'] = $sellCashlen;
				$buylen = strlen($r['buy'].';');
				if($buylen > $len['buy']) $len['buy'] = $buylen;
				$selllen = strlen($r['sell'].';');
				if($selllen > $len['sell']) $len['sell'] = $selllen;
			}
		}
		echo "Symbol\t".str_pad('Qty', $len['qty'])."\t".str_pad('BuyCash', $len['buyCash'])."\t".str_pad('SellCash', $len['sellCash'])."\t".str_pad('Buy', $len['buy'])."\t".str_pad('Sell', $len['sell'])."\n";
		foreach($this->rates as $key => $qrates) {
			echo $this->sms[$key]['sm_name'].":";
			foreach($qrates as $qty => $r) {
				echo "\t".str_pad($qty.';', $len['qty'])."\t".str_pad($r['buyCash'].';', $len['buyCash'])."\t".str_pad($r['sellCash'].';', $len['sellCash'])."\t".str_pad($r['buy'].';', $len['buy'])."\t".$r['sell']."\n";
			}
		}
	}
}

function findData(&$data, $startTag, $endTag, $offset=0, $ignorCase=false, $debug=false) {
	if(!is_int($offset) || strlen($data) < $offset)
		return false;

	$spos = $ignorCase === false ? strpos($data, $startTag, $offset) : stripos($data, $startTag, $offset);
	if($debug) echo '<h1>'.var_export($startTag,true).': '.var_export($spos,true)."</h1>\n";
	if(is_int($spos)) {
		$spos += strlen($startTag);
		$epos = $ignorCase === false ? strpos($data, $endTag, $spos) : stripos($data, $endTag, $spos);
		if(is_int($epos)) {
			$ret['data'] = substr($data, $spos, $epos - $spos);
			$ret['start'] = $spos;
			$ret['end'] = $epos;
			return $ret;
		}
	}
	return false;
}

?>