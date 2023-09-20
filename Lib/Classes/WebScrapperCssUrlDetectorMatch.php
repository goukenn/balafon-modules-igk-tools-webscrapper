<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperCssUrlDetectorMatch.php
// @date: 20230914 15:22:38
namespace igk\tools\webscrapper;


///<summary></summary>
/**
* 
* @package igk\tools\webscrapper
*/
class WebScrapperCssUrlDetectorMatch{
    var $expression;
    var $uri;    
    var $domain;
    var $scheme;
    var $path;
    var $extra;
    var $fromUri;
    public function __toString(){
        return $this->uri;
    }
}