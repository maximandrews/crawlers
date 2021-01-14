#!/usr/local/bin/php
<?php
if(getenv('DEBUG')) $totaltime = microtime(true);
define('BASE_PATH', str_replace('\\', '/', dirname(__FILE__)));
require_once BASE_PATH.'/pages_fetcher.php';


$class = isset($argv) && isset($argv[1]) && strlen($argv[1]) > 0 ? $argv[1]:NULL;

$filename = BASE_PATH.'/../parsers/'.$class.'/'.$class.'.php';
if(!file_exists($filename)) {
	echo "File $filename doesn't exists!\n";
	exit;
}

require_once $filename;

if(!class_exists($class)) {
	echo "Class: $class doesn't exists in file $filename!\n";
	exit;
}

$fetcher = new pagesFetcher;

$obj = new $class;
$obj->Init();

while($urls = $obj->getURLs()) {
	$contents = Array();
	foreach($urls as $method => $methodURLs) {
		$contents[$method] = Array();
		$fetcher->prepareQueue($methodURLs);
		$fetcher->fetchPages();
		$contents[$method] = $fetcher->getFetchedPages();
	}

	foreach($contents as $method => $alldata) {
		if(method_exists($obj, $method))
			$obj->$method($alldata);
	}
}

$obj->makeOut();
?>