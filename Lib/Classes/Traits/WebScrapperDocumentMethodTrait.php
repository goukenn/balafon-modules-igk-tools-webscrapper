<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperDocumentMethodTrait.php
// @date: 20230914 13:36:11
namespace igk\tools\webscrapper\Traits;


///<summary></summary>
/**
* 
* @package igk\tools\webscrapper\Traits
*/
trait WebScrapperDocumentMethodTrait{
    protected $m_title;
    protected $m_favicon;

    /**
     * get the parsed document title
     * @return mixed 
     */
    public function getTitle(){
        return $this->m_title;
    }
    public function getFavicon(){
        return $this->m_favicon;
    }
}