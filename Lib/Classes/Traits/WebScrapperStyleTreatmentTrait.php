<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperStyleTreatmentTrait.php
// @date: 20230914 14:24:47
namespace igk\tools\webscrapper\Traits;

use IGK\System\Console\Logger;
use IGK\System\Http\HttpUtility;
use IGK\System\IO\Path;
use igk\tools\webscrapper\WebScrapperConstants;
use igk\tools\webscrapper\WebScrapperCssUrlDetector;
use igk\tools\webscrapper\WebScrapperGetResource;
use IGKException;
use IGKValidator;

///<summary></summary>
/**
* 
* @package igk\tools\webscrapper\Traits
*/
trait WebScrapperStyleTreatmentTrait{
    protected function getBaseHost():string{
        return HttpUtility::GetBaseHost($this->base);
    
    }
    public function treatStyleSource(string $source, string $outdir='/'){ 
        $uris = & $this->m_resources;
        return self::TreatStyleSourceContent($this, $source, $uris, $outdir);
    }

    public static function TreatStyleSourceContent($document, string $source, & $uris, string $outdir='/' ){
   
        $rgx = sprintf(WebScrapperConstants::URL_BRACKET_RX, '2');
        if ($c = preg_match_all("/(\s*|,|;)?@import\s*" . $rgx . "/i", $source, $tab)){
            for ($i = 0; $i < $c; $i++) {
                $path = $tab['path'][$i];
                $extra = $tab['extra'][$i];
                $url = $path. $extra;
                // if (!IGKValidator::IsUri($url))
                //     $url = Path::Combine($outdir, $url);
                $source = self::_UpdateResourceURL($document, $url, $uris, $source, $outdir, $extra);
            }
        }

        $re = new WebScrapperCssUrlDetector;
        if ($b = $re->cssUrl($source)){
            usort($b, function($a, $b){
                return strcmp($b->uri, $a->uri);
            });
            while(count($b)>0){
                $q = array_shift($b);
                // parsing and convert to resources 
                $url = $q->uri;
                $extra = igk_getv(parse_url($q->uri), 'query');
                $v_doutdir = $outdir;
                if ($url[0]=='/'){
                    $v_doutdir = '/';
                }
                // replace detected expression with 
                $exp = self::_UpdateResourceURL($document, $url, $uris, $q->expression, $v_doutdir, $extra, 'auto');
                $source = str_replace($q->expression, $exp, $source);
                //$source = self::_UpdateResourceURL($document, $url, $uris, $source, $outdir, $extra, 'auto');
            }
        }
        
        return $source;
    }
    /**
     * for single uri replace detected uri content
     * @param mixed $document 
     * @param string $url 
     * @param mixed $uris 
     * @param string $source 
     * @param string $baseHost 
     * @param string $outdir 
     * @param null|string $extra 
     * @param string $type 
     * @return string 
     * @throws IGKException 
     */
    private static function _UpdateResourceURL($document, string $url, & $uris, string $source, string $outdir, ?string $extra, $type='css'){
        $path = explode("?", $url, 2)[0];
        $key_url = $url;
        $replace = true;
        $baseHost = $document->getBaseHost();

        if (IGKValidator::IsUri($url)){
            $host = HttpUtility::GetBaseHost($url); 
            $q = parse_url($url);
            
            $path = ltrim(igk_getv($q, 'path', '/'), '/');
            if(empty($path)){
                // do not replace 
                return $source;
            }
            
            // on the same host
            if ($host != $baseHost ){
                $c_url = $url;
                $c_buri = null;
                $scheme = igk_getv($q, "scheme");
                if ($extra){
                    $c_url = $document->getFileUriHash($path, $extra, $type);
                    $c_url = $scheme."://".igk_uri(Path::Combine(HttpUtility::GetBaseHost($url), $c_url )); //  sprintf("%s")
                    $c_buri = new WebScrapperGetResource($c_url, $document->hashAlogithm,$document->externalAssetsLocation);
                }
                $c_buri = $c_buri ?? new WebScrapperGetResource($c_url, $document->hashAlogithm);
                $source = str_replace($url, $c_buri.'', $source);                
                $uris[$url] = $c_buri;
                return $source;
            } 
            $key_url = $path;
            if ($extra){
                $key_url.= igk_str_assert_prepend(ltrim($extra,'?'), '?');
            }
            // .igk_str_assert_prepend($extra, '?');
        } else {
            if ($outdir!="/"){
                $key_url = Path::CombineAndFlattenPath($outdir, $key_url);
                $replace = false;
            }
        }
        $file = Path::CombineAndFlattenPath($outdir, $path);
        $bname = basename($file);
        if ($file && ($outdir=="/") && ($file[0]!='/')){
            $file = '/'.$file;
        }

        $ext = (strpos($bname, '.')>0)?igk_io_path_ext($bname) : null;
        if (((!$ext)&&($type!='auto')) || ($ext && ($ext!='html') && ($type=='html'))){            
            $file.=".".$type;
        }
        if ($extra){
            $extra_url = $document->getFileUriHash($file, $extra, $type);            
            $file = $extra_url;
        }
        if ($replace){
            $source = str_replace($url, $file, $source);
        }
        // store uri file - 
        $key_url = $document->_resolveUri($key_url);

        $uris[$key_url] = $file; 
        Logger::warn ('add : '.$key_url);
        return $source;
    }
    /**
     * detect source map and mark of file
     * @param mixed $document 
     * @param mixed $uris 
     * @param string $baseUrl 
     * @param mixed $source 
     * @param null|string $baseHost 
     * @param mixed $outdir 
     * @param mixed $extra 
     * @return mixed 
     * @throws IGKException 
     */
    private static function _UpdateSourceMap($document, & $uris, string $baseUrl, $source, ?string $baseHost, $outdir, $extra){
        $baseHost = $baseHost ?? $document->getBaseHost();
        // detect source source map
        if (preg_match('/sourceMappingURL\s*=\s*(?P<url>[^ #=]+)/i', $source, $tab)){
            $url = igk_str_remove_quote(trim($tab['url']));
            if (!IGKValidator::IsUri($url)){
                $url = Path::CombineAndFlattenPath($baseUrl, $url);
                $outdir = "/";
            }
            if ($document->removeSourceMap){
                $source = str_replace($tab[0], '', $source);
            }else {
                // resolve source map
                $source = self::_UpdateResourceURL($document, $url, $uris, $source, $outdir,$extra, 'map');
            }
        }
        return $source;
    }

    /**
     * use in loop to get download content
     * @param mixed $document 
     * @param string $content 
     * @param mixed $downloads 
     * @param mixed $res 
     * @return void 
     */
    public static function TreatCssContent($document,string $content, & $res, $outdir="/"){
         
        $content = self::TreatStyleSourceContent($document, $content, $res, $outdir);
        return $content;
    }
}