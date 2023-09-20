<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperUriInfo.php
// @date: 20230914 13:45:15
namespace igk\tools\webscrapper\Traits;


///<summary></summary>
/**
* use to store resolved uri info
* @package igk\tools\webscrapper\Traits
*/
class WebScrapperUriInfo{
    /**
     * source url
     * @var mixed
     */
    var $url;
    /**
     * stored file
     * @var mixed
     */
    var $file;

    /**
     * parsed url 
     * @var mixed
     */
    var $parseUrl;

    /**
     * 
     * @var mixed
     */
    var $target;

    /**
     * store target attribute name
     * @var mixed
     */
    var $attrName;

    var $type;

    /**
     * extra flag
     * @var ?string
     */
    var $extra;
}