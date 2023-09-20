<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperJSModuleTrait.php
// @date: 20230915 11:47:49
namespace igk\tools\webscrapper\Traits;

use IGK\System\IO\Path;
use IGKValidator;

///<summary></summary>
/**
* 
* @package igk\tools\webscrapper\Traits
*/
trait WebScrapperJSModuleTrait{
    protected $m_module_js = [];

    /**
     * resolve main module content 
     * @param string $content 
     * @param mixed $res 
     * @return string 
     */
    public function resolveJSModuleContent(string $content, & $res, string $baseURL, string $outDir){
        $offset=  0;
        $is_uri = $baseURL ? IGKValidator::IsUri($baseURL) : false;
      
        while(preg_match("/from\s+(?P<url>'[^']+'|\"[^\"]+\")/i",$content, $tab, PREG_OFFSET_CAPTURE, $offset)){
        
            list($p, $offset) = $tab['url'];
            $ch = $content[$offset];
            $pos = $offset;
            $t = igk_str_remove_quote(igk_str_read_brank($content, $pos, $ch, $ch));
            $file = self::_UpdateResourceURL($this, $t, $res, $t, $outDir, null, 'jsmodule');
            $content = substr($content, 0, $offset)."'$file'". substr($content, $pos+1);
            $offset = $pos;
        }
        // + | detect inline resource to download
        $ext = '\.(svg|png|jp(e)?g)';
        if($c = preg_match_all("/'[^']+{$ext}'|\"[^\"]+{$ext}\"/", $content, $tab)){
            $v_tpath = $outDir;
            for($i = 0 ; $i<$c;$i++ ){
                $v_tpath = $outDir;
                $type = $tab[2][$i];
                $t = $s = igk_str_remove_quote($tab[0][$i]);
                $replace = false;
                if (str_starts_with($t, '/')){
                    // base absolute path
                    $t = ltrim($t, '/');
                    $v_tpath = '/';
                }else{
                    // relative to new path
                }
                $file = self::_UpdateResourceURL($this, $t, $res, $s, $v_tpath, null, $type);
                if ($replace){
                    $content = str_replace($t, $file, $content);
                }
            }
        }
        // detect import 
        if($c = preg_match_all("/(^|\s+)import\s*\(\s*(?P<url>'[^']*'|\"[^\"]*\")\s*\)/", $content, $tab)){
            $v_tpath = $outDir;
            for($i = 0 ; $i<$c;$i++ ){
                $v_tpath = $outDir; 
                $t = $s = igk_str_remove_quote($tab['url'][$i]);
                $replace = false;
                if (str_starts_with($t, '/')){
                    // base absolute path
                    $t = ltrim($t, '/');
                    $v_tpath = '/';
                }else{
                    // relative to new path
                }
                $ref = $t;
                if ($is_uri){
                    $ref = Path::CombineAndFlattenPath($baseURL, $t);
                    $v_tpath = '/';
                }
                $file = self::_UpdateResourceURL($this, $ref, $res, $s, $v_tpath, null, "auto");
                if ($replace){
                    $content = str_replace($t, $file, $content);
                }
            }
        }
        return $content;
    }
}