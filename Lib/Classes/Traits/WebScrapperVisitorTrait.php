<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperVisitorTrait.php
// @date: 20230914 13:01:45
namespace igk\tools\webscrapper\Traits;

use IGK\System\Html\Dom\HtmlNode;
use IGK\System\Html\HtmlAttributeValue;
use IGK\System\Html\HtmlAttributeValueListener;
use IGK\System\Html\HtmlUtils;
use IGK\System\Http\HttpUtility;
use IGK\System\IO\Path;
use IGKException;
use IGKValidator;

///<summary></summary>
/**
* 
* @package igk\tools\webscrapper\Traits
*/
trait WebScrapperVisitorTrait{
    protected $m_container;
    protected $m_scripts = [];
    protected $m_images = [];
    protected $m_links = [];
    /**
     * store url resources array<URL, location> 
     * @var array
     */
    protected $m_resources = [];
    protected $m_anchors = [];
    /**
     * change auto resolution on download
     * @var array
     */
    protected $m_auto_resolution = [];

    /**
     * retrieve resources to load. <url, filename> 
     * @return array 
     */
    public function resources(){
        return $this->m_resources;
    }
    /**
     * get loaded scripts
     * @return array 
     */
    public function scripts(){
        return $this->m_scripts;
    }
    /**
     * get document images
     * @return array 
     */
    public function images(){
        return $this->m_images;
    }
    public function links(){
        return $this->m_links;
    }
    public function anchors(){
        return $this->m_anchors;
    }
    protected function visit_head(){
        $this->m_container = 'head';
    }
    protected function visit_body(){
        $this->m_container = 'head';
    }
    protected function visit_title($t){
        if ($this->m_container=='head'){
            $this->m_title = $t->getContent(); 
        }
    }
    protected function visit_base($t){
        if ($this->m_container=='head'){
            $t['href'] = '/';
        }
    }
    protected function visit_script($t){
        $key = 'src';
        if ($src = $t->getAttribute($key)){
            $count = count($this->m_scripts);
            self::BindScrapperUriInfo($this, $this->m_scripts, $src, $t, $key, 'js');
            if ($t->getAttribute('type') == 'module'){
                if ($count < count($this->m_scripts)){
                    // trait modules 
                    $uri = $this->m_scripts[$count]->url;
                    if (!IGKValidator::IsUri($uri)){
                        $uri = "/".ltrim(Path::CombineAndFlattenPath('/', $uri), '/');
                    }                    
                    $this->m_scripts[$count]->extra = 'jsmodule';
                    $this->m_module_js[$uri] = 1;
                }
            }
        } else{
            // + | trait inline script content
        }
       
    }
    protected function visit_img($t){
        $key = 'src';
        if ($src = $t->getAttribute($key)){
            self::BindScrapperUriInfo($this, $this->m_images, $src, $t, $key, 'img');
        } 
    }

    protected function visit_a($t){
        if ($href = $t['href']){
            if (in_array($href,['#', '/','./','../']) || igk_str_startwith($href, "#") || igk_str_startwith($href, "javascript:") || igk_str_startwith($href, "mailto:")){
                return;
            }
            if (IGKValidator::IsUri($href)){
                $_dom = HttpUtility::GetBaseHost($href);
                if ($_dom != $this->getBaseHost()){
                    return;
                }
            }
            self::BindScrapperUriInfo($this, $this->m_anchors, $href, $t, "href", 'html');
        }
    }
   
    /**
     * visit style element
     * @param mixed $t 
     * @return void 
     */
    protected function visit_style($t){
        $src = $t->getContent();
        // treat style - 
        if (!empty($src)){
            $src = $this->treatStyleSource($src);
            $t->setContent($src);
        }
    }
    protected function visit_link($t){
        $key = 'href';
        if ($src = $t->getAttribute($key)){
            $ctype = $t['rel'];
            switch ($ctype) {
                case 'stylesheet':
                case 'icon':
                case 'shortcut icon':
                case 'apple-touch-icon':
                    $type = 'auto';
                    if ($ctype=='stylesheet'){
                        $type = 'css';
                    }
                    if ($ctype=='icon'){
                        $this->m_favicon = $t;
                    }
                    self::BindScrapperUriInfo($this, $this->m_links, $src, $t, $key, $type);
                    break;
            }
            $this->_integrity_check($t);            
        } 
    }
    protected function _integrity_check($link)
    {
        if ($i = $link['integrity']) {
            $link['integrity'] = new HtmlAttributeValue($i);
        }
        if ($i = $link['crossorigin']) {
            $link['crossorigin'] = new HtmlAttributeValue($i);
        }
    }
    /**
     * 
     * @param static $document 
     * @param mixed $tab 
     * @param string $url 
     * @param mixed $target 
     * @param mixed $attribName 
     * @param string $type 
     * @return void 
     * @throws IGKException 
     */
    private static function BindScrapperUriInfo($document, & $tab, string $url, ?HtmlNode $target, $attribName, $type="css"){

        if (igk_str_startwith($url,"data:")){
            return;
        }
        $v_base = $document->base ?? '/';
        $v_info = new WebScrapperUriInfo;
        $v_info->url = $url;
        $v_info->target = $target;
        $v_info->attrName = $attribName;
        $v_info->type = $type;
        $tab[] = $v_info;

        $outdir = igk_getv(parse_url($v_base), 'path') ?? '/';       
        $uris = & $document->m_resources;
        $q = parse_url($url);
        $extra = igk_getv($q, 'query');
        $hash_fragment = igk_getv($q, 'fragment');
        $v_info->file = self::_UpdateResourceURL($document, $url, $uris, $url, $outdir, $extra, $type);
        if ($hash_fragment){
            $hash_fragment="#".$hash_fragment;
        }
        if ($v_info->file != $url){
            $cfile = $v_info->file.$hash_fragment;
            if ($outdir=="/")
            {
                $cfile = ltrim($cfile,'/');
            }
            $v_info->target[$v_info->attrName] = $cfile; // '--'.$v_info->file.$hash_fragment;
        }
        if ($type=='auto'){
            if (!$document->m_auto_resolution){
                $document->m_auto_resolution = [];
            }
            $document->m_auto_resolution[$url][]= $v_info;
        } 
    }
}