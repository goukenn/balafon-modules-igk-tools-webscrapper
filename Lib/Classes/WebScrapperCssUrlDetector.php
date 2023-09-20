<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperCssUrlDetector.php
// @date: 20230914 15:20:16
namespace igk\tools\webscrapper;


///<summary></summary>
/**
 * 
 * @package igk\tools\webscrapper
 */
class WebScrapperCssUrlDetector
{
    const CSS_URL = '/url\s*\(\s*(?P<bracket>(\'|"))?(?P<path>(((\.)?\.\/|\/|(?P<protocol>[a-z0-9]+):\/\/)|(?P<inline_data>data):|[a-z0-9]+)[^\'\)\#\?\,\+\: ]+(?P<port>:[0-9]+)?[^\'\)\#\?\,\+\: ]+)(?P<extra>([^\) ]+))?(?(bracket)\\1)\)/i';
    public function __construct()
    {
    }
    /**
     * match url in current source  
     * @param string $src 
     * @return array|false 
     * @throws IGKException 
     */
    public function match(string $src): array
    {
        $src = igk_css_rm_comment($src);
        $offset  = 0;
        $tab = [];
        while (($pos = strpos($src, 'url', $offset)) !== false) {
            $offset = $pos + 3;
            $read = false;
            $method = false;
            $url = null;
            while (!$read) {
                $ch = $src[$offset];
                switch ($ch) {
                    case ' ':
                        break;
                    case '"';
                    case '\'':
                        $url = igk_str_remove_quote(igk_str_read_brank($src, $offset, $ch, $ch));
                        $read = true;
                        if ($method){
                            // + | skip to end bracket;
                            if (($pos = strpos($src, ')', $offset))!==false){
                                $offset = $pos;
                            } else {
                                $offset = strlen($src);
                            }
                        }
                        break;
                    case '(':
                        $method = true;
                        break;
                    default:
                        if ($method) {
                            if (!empty($ch)) {
                                $url = trim(igk_str_read_brank($src, $offset, ")", "("), ')( ');
                                $read = true;
                            }
                        } else {
                            $read = true;
                        }
                        break;
                }
                $offset++;
            }
            if ($url) {
                $tab[] = $url;
            }
        }
        return $tab;
    }

    /**
     * detect css url() from source data
     * @param mixed $src source string 
     * @param null|string $form from data
     * @return null|UriDectectorMatch[] 
     * @throws IGKException 
     */
    public function cssUrl($src, ?string $from = null, $ingoreInline = true)
    {
        $tab =  null;
        $src = igk_css_rm_comment($src);
        if ($g = preg_match_all(self::CSS_URL, $src, $out)) {
            $_idata = igk_getv($out, 'inline_data');
            for ($i = 0; $i < $g; $i++) {
                if ($ingoreInline && $_idata && !empty($_idata[$i])) {
                    continue;
                }
                $match = new WebScrapperCssUrlDetectorMatch;
                $r = igk_getv($out, 'path');
                $extra = igk_getv($out, 'extra');
                $protocol = igk_getv($out, 'protocol');
                $gr = $r ? trim(igk_getv($r, $i) ?? '', '"\'') : null;
                if ($gr && $protocol && empty($protocol[$i])  && (strpos($gr, "../") === false) && (strpos($gr, "./") === false)) {
                    //from current file.
                    $gr = './' . ltrim($gr, '/');
                }
                $match->expression = $out[0][$i];
                $match->path = $gr ? $gr : null; //$v;
                $match->extra = igk_getv($extra, $i);
                //  $match->match_path = $gr;
                if ($match->path && ($v_tg = parse_url($match->path))) {
                    $match->domain = igk_getv($v_tg, 'host');
                    $match->scheme = igk_getv($v_tg, 'scheme');
                }
                $match->uri = trim($r[$i], '"\'');
                $tab[] = $match;
                if ($from) {
                    // + | update from info
                    $match->fromUri = $from;
                }
            }
        }
        return $tab;
    }
}
