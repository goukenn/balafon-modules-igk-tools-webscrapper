<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperGetResource.php
// @date: 20230914 15:01:02
namespace igk\tools\webscrapper;

use IGK\System\IO\Path;

///<summary></summary>
/**
* 
* @package igk\tools\webscrapper
*/
final class WebScrapperGetResource{
    var $url;
    var $res;
    public function __construct(string $url, string $algo, $out='/assets/ext')
    {
        $this->url = $url;
        $q = parse_url($url);
        $path = igk_getv($q,'path');
        $host = igk_getv($q,'host') ?? igk_die("resource host is missing");
        $host = hash($algo, $host);
        $this->res = Path::CombineAndFlattenPath($out, $host, $path);
    }
    /**
     * retrieve store resource data 
     * @return string 
     */
    public function __toString()
    {
        return $this->res;
    }
}