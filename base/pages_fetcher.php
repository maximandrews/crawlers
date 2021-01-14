<?php

class pagesFetcher {
	private $Conn;
	private $maxThreads = 0;
	private $pagesQueue = Array();
	private $activePages = Array();
	private $fetchedPages = Array();
	private $mh;

	public function __construct() {
		$this->maxThreads = (defined('MAX_THREADS') ? MAX_THREADS:5);
		$this->mh = curl_multi_init();
	}

	public function __destruct() {
		curl_multi_close($this->mh);
	}

	public function fetchPages() {
		for($i=0; $i < $this->maxThreads; $i++){
			if(!$this->initCURL())
				break;
		}

		$running = null;
		do {
			$mcRes = curl_multi_exec($this->mh, $running);
			while($done = curl_multi_info_read($this->mh)) {
				//print_r($done);
				$this->savePageData($done['handle']);
				curl_multi_remove_handle($this->mh, $done['handle']);
				curl_close($done['handle']);
				$this->initCURL();
			}
			curl_multi_select($this->mh);
		} while ($mcRes === CURLM_CALL_MULTI_PERFORM || $running);
	}

	private function initCURL() {
	//	echo 'me'.'<br />';
		$data = $this->initURL();
		if($data && is_array($data) && isset($data['url'])) {
			$ch = curl_init();

			$headers = Array(
	    	'User-Agent: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/28.0.1500.72 Safari/537.36',
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language: en-US,en;q=0.8,lv;q=0.6,ru;q=0.4',
				'Accept-Encoding: gzip,deflate,sdch',
				//'Cache-Control: max-age=0',
				'Connection: keep-alive');

			if(isset($data['pc_options']) && isset($data['pc_options']['headers']) && is_array($data['pc_options']['headers']) && count($data['pc_options']['headers']) > 0)
				$headers = array_merge($headers, $data['pc_options']['headers']);

			curl_setopt_array($ch, Array(
				CURLOPT_URL => $data['url'],
				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
				CURLOPT_HEADER => false,
				CURLOPT_AUTOREFERER => True,
				CURLOPT_FOLLOWLOCATION => True,
				CURLOPT_TIMEOUT => 60,
				CURLOPT_CONNECTTIMEOUT => 60,
				CURLOPT_COOKIEFILE => BASE_PATH.'/cookie.jar',
				CURLOPT_COOKIEJAR => BASE_PATH.'/cookie.jar',
				CURLOPT_ENCODING => '',
				CURLOPT_HTTPHEADER => $headers,
				CURLOPT_HEADERFUNCTION => array(&$this, 'processHeaders'),
				CURLOPT_WRITEFUNCTION => array(&$this, 'processContent'),
				CURLOPT_VERBOSE => False
			));

			if(strpos($data['url'], 'https') === 0) {
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, False);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			}

			if(isset($data['pc_options']) && isset($data['pc_options']['cookie']))
				curl_setopt($ch, CURLOPT_COOKIE, $data['pc_options']['cookie']);

	  	if(isset($data['pc_options']) && isset($data['pc_options']['method']) && $data['pc_options']['method'] == 'post') {
	  		curl_setopt($ch, CURLOPT_POST, True);
				if(isset($data['pc_options']['params']) && (is_array($data['pc_options']['params']) && count($data['pc_options']['params']) > 0 || is_string($data['pc_options']['params']) && strlen($data['pc_options']['params']) > 0))
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data['pc_options']['params']);
	  	}else
	  		curl_setopt($ch, CURLOPT_HTTPGET, True);

	    if(isset($argv) && is_array($argv) && count($argv) > 1) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    $id = $this->getUID($data['url'], $ch);
	    $this->activePages[$id] = $data;
			//echo "url: $url; init $ch\n";
			/*
	    if(strlen($this->activePages[$id]['ws_username']) && strlen($this->activePages[$id]['ws_username'])){
	    	curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	    	curl_setopt($ch, CURLOPT_USERPWD, $this->activePages[$id]['ws_username'].':'.$this->activePages[$id]['ws_password']);  	
	    }*/
	    curl_multi_add_handle($this->mh, $ch);
	    return true;
	  }
    return false;
	}

	private function initURL() {
		if(count($this->pagesQueue) == 0)
			return false;

		$data = array_shift($this->pagesQueue);
		if(!preg_match("/^https?:\/\/[\d\w\.]+/is", $data['pc_url'])) {
			$data['pc_options'] = json_decode($data['pc_url'], true);
			$data['url'] = $data['pc_options']['url'];
		}else
			$data['url'] = $data['pc_url'];

		$data['headers'] = '';
		$data['content'] = '';
		return $data;
	}

	public function prepareQueue($urls) {

		foreach($urls as $id => $url) {
			$url = is_array($url) ? json_encode($url):$url;
			$this->pagesQueue[] = Array('pq_id' => $id, 'pc_url' => $url, 'pq_url_sha1' => sha1($url));
		}

		return isset($this->pagesQueue) && is_array($this->pagesQueue) && count($this->pagesQueue) > 0;
	}

	private function getUID($url, $res) {
		return bin2hex(mhash(MHASH_SHA1, $url.$res));
	}

	private function processHeaders($ch, $data){
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$id = $this->getUID($url, $ch);
		//echo "url: $url; headers: ".strlen($data)."\n";
		$this->activePages[$id]['headers'] .= $data;
    return strlen($data);
	}

	private function processContent($ch, $data) {
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$id = $this->getUID($url, $ch);
		//echo "url: $url; data: ".strlen($data)."\n";
		$this->activePages[$id]['content'] .= $data;
    return strlen($data);
	}

	private function savePageData($ch) {
		$url = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
		$id = $this->getUID($url, $ch);
		//echo "url: $url; cleanup; $ch\n\n";

		$apage = $this->activePages[$id];
		$this->fetchedPages[$id] = Array(
			'pc_headers' => $apage['headers'],
			'pc_content' => $apage['content'],
			'pc_url' => $apage['url'],
			'pq_url_sha1' => $apage['pq_url_sha1']
		);

		unset($this->activePages[$id]);
	}

	function getFetchedPages() {
		$fetched = $this->fetchedPages;
		$this->fetchedPages = Array();
		return $fetched;
	}
}
?>