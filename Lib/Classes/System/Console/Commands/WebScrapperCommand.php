<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperCommand.php
// @date: 20230914 12:48:40
namespace igk\tools\webscrapper\System\Console\Commands;

use IGK\System\Console\AppExecCommand;
use IGK\System\Console\Logger;
use IGK\System\Http\CurlHttpClient;
use igk\tools\webscrapper\WebScrapperDocument;
use IGKValidator;

///<summary></summary>
/**
* 
* @package igk\tools\webscrapper\System\Console\Commands
*/
class WebScrapperCommand extends AppExecCommand{
	var $command="--tool:webscrapper";
	var $desc="webscrapper web site";
	var $category="tools";
	var $options=[

	];
	var $usage='';
	public function exec($command,?string $url=null) { 
		empty($url) && igk_die('required URL');
		if (!IGKValidator::IsUri($url)){
			igk_die('not a valid url');
		}
		$client = new CurlHttpClient;
		$client->accept = 'text/html';
		$client->followLocation = true;
		igk_set_timeout(0);
		if ($content = $client->request($url)){
			return self::ScrapContent($command, $url, $content);
		}
		return -1;
	}
	public static function ScrapContent($command, string $url, string $content){
		$outdir = igk_getv($command->options, '--outdir');
		$parser = new WebScrapperDocument();
		$parser->base = $url;
		$parser->parseContent($content);
		if ($outdir){
			$t = igk_start_time(__METHOD__);
			$parser->exportTo($outdir);
			Logger::success('done: '.$outdir. ' '.igk_execute_time(__METHOD__));
			
		} else{
			Logger::print($parser->render());
		}
		return 0;
	}
}