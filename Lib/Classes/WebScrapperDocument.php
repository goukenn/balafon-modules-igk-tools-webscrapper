<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperDocument.php
// @date: 20230914 12:52:55
namespace igk\tools\webscrapper;

use IGK\Helper\IO;
use IGK\System\Console\Logger;
use IGK\System\Http\CurlHttpClient;
use IGK\System\Http\HttpUtility;
use IGK\System\Http\IHttpClient;
use IGK\System\IO\Path;
use IGKException;
use IGKValidator;


///<summary></summary>
/**
 * 
 * @package igk\tools\webscrapper
 */
class WebScrapperDocument
{
    use Traits\WebScrapperVisitorTrait;
    use Traits\WebScrapperDocumentMethodTrait;
    use Traits\WebScrapperStyleTreatmentTrait;
    use Traits\WebScrapperJSModuleTrait;

    public $hashAlogithm = 'crc32b';

    public $removeSourceMap = false;

    private $m_httpClient;

    /**
     * 
     * @var ?bool
     */
    var $skipAutoIndex;


    /**
     * primary index file
     * @var string
     */
    var $index = 'index.html';

    /**
     * get or set external asset location.
     * @var string
     */
    var $externalAssetsLocation = '/assets/ext/';

    /**
     * depth level
     * @var int
     */
    var $level = 0;
    /**
     * base url
     * @var ?string
     */
    var $base;
    /**
     * parent scrapper document
     * @var mixed
     */
    private $m_parse_node;

    /**
     * download resources
     * @var mixed
     */
    private $m_downloads;

    /**
     * last parsed source
     * @var ?string
     */
    private $m_source;

    /**
     * is reference tag name
     * @var bool
     */
    private $m_is_reftag;

    /**
     * maxi depth resource level
     * @var int
     */
    private $m_maxLevel = 5;

    /**
     * is source export to WebScrapper
     * @var bool
     */
    private $m_isSource = true;

    /**
     * load ressource keys;
     * @var mixed
     */
    private $m_load_res = null;

    /**
     * in exportTo mark ressource index
     * @var int
     */
    private $m_res_index  = 0;

    public function getMaxLevel(): int
    {
        return $this->m_maxLevel;
    }
    public function setMaxLevel(int $value)
    {
        $this->m_maxLevel = $value;
    }
    public function __construct()
    {
        $this->setHttpClient(null);
    }
    public function setHttpClient(?IHttpClient $client)
    {
        $this->m_httpClient = $client ?? $this->createHttpClient() ?? igk_die("require http client");
    }
    protected function createHttpClient()
    {
        return new CurlHttpClient;
    }

    /**
     * parse webcontent 
     * @param string $content 
     * @return void 
     */
    public function parseContent(string $content): bool
    {
        $this->m_favicon = null;
        $this->m_title = null;
        $n = igk_create_notagnode();
        if ($n->load($content)) {
            $n->getElementsByTagName(function ($n) {
                $tn = $n->getTagName();
                if ($tn) {
                    $this->m_is_reftag = preg_match("/^(" . WebScrapperConstants::REF_TAG . ")$/i", $tn);
                    if (method_exists($this, $fc = 'visit_' . $tn)) {
                        $this->$fc($n);
                    }
                    return true;
                }
                return false;
            });
            $this->m_parse_node = $n;
            $this->m_source = $content;
            return true;
        }
        return false;
    }

    /**
     * render parsed node
     * @param mixed $options 
     * @return null|string 
     */
    public function render($options = null): ?string
    {
        if ($this->m_parse_node) {
            return $this->m_parse_node->render($options);
        }
        return null;
    }

    /**
     * resolve uri to base path
     * @param string $path 
     * @return string 
     * @throws IGKException 
     */
    public function _resolveUri(string $path)
    {
        if (IGKValidator::IsUri($path)) {
            return $path;
        }
        $u = $this->base ?? '/';
        $q = parse_url($u);
        if ($path && ($path[0] == "/")) {
            // base path 
            return $path;
        }
        $base = igk_getv($q, 'path', '/');
        $uri =  Path::CombineAndFlattenPath($base, $path);
        if ($uri && ($base=="/") && ($uri[0]!="/")){
            $uri = "/".$uri;
        }
        return $uri;
    }
    /**
     * resolv base uri
     * @return mixed 
     * @throws IGKException 
     */
    public function getBaseUri()
    {
        $u = $this->base ?? '/';
        $q = parse_url($u);
        $scheme = igk_getv($q, "scheme");
        if ($host = igk_getv($q, "host")) {
            if ($scheme) {
                $host = $scheme . "://" . $host;
            }
            if ($port = igk_getv($q, 'port'))
                $host .= ':' . $port;

            return $host;
        }
        return $u;
    }
    /**
     * export to directory
     * @param string $outdir 
     * @return void 
     */
    public function exportTo(string $outdir)
    {
        IO::CreateDir($outdir);
        $bck = getcwd();
        chdir($outdir);
        static $sm_shared_download = null;
        static $sm_initiator = null;
        $v_outindex = 'index.html';
        if (is_null($sm_shared_download)) {
            $sm_shared_download = [
                Path::CombineAndFlattenPath($this->base, $v_outindex) => $v_outindex
            ];
            $sm_initiator = $this;
        }

        if (!$this->m_favicon) {
            // + | --------------------------------------------------------------------
            // + | try get favicon.ico
            // + |
            $this->m_resources["/favicon.ico"] = "favicon.ico";
        }
        // - resource list 
        $res = array_slice($this->m_resources, $this->m_res_index); // copy resources
        // - current downloads
        $this->m_downloads = [];
        $downloads = &$this->m_downloads;
        $baseHost = $this->getBaseHost();
        $v_count = $this->m_res_index;

        while (count($res) > 0) {
            $v_count++;
            $k = key($res);
            $v = array_shift($res);
            $uri = $k;
            $v_update_sourcemap = false;

            // + | ------------------
            // + | resolv current uri
            // + |
            // + | Logger::warn('resolv: save to '. $k);
            

            if ($this->m_load_res && ($k == $this->m_load_res)) {
                // + | skip 
                continue;
                // igk_wln_e("done");
            }
            $v_fulluri = $k;

            if (!IGKValidator::IsUri($k)) {
                // get base full uri  
                $v_fulluri = Path::Combine($this->getBaseUri(), Path::CombineAndFlattenPath('/', $k));
            }
            if (isset($sm_shared_download[$v_fulluri])) {
                continue;
            }
            if (isset($downloads[$v_fulluri])) {
                continue;
            }
            if (!isset($this->m_resources[$k])){
                $this->m_resources[$k] = $v;
            }
            Logger::info('-- request -- ' . $uri);
            $content = $this->_request($uri);
            $downloads[$v_fulluri] = $v;
            $sm_shared_download[$v_fulluri] = $v;
            $status = $this->m_httpClient->getStatus();
            if ($status == 200) {
                $ext = igk_io_path_ext($v);


                $new_path = dirname($v);
                if ($new_path == '.') {
                    $new_path = '/';
                }
                // resolve auto fields
                if (isset($this->m_auto_resolution[$uri])) {
                    // 
                    $headers = $this->m_httpClient->getRequestHeaderResponse();
                    $content_type = explode(';', igk_getv($headers, 'Content-Type', 2))[0];
                    $n_ext = HttpUtility::GetExtensionFromContentType($content_type);


                    $p = igk_io_basenamewithoutext(basename($v));
                    $v = Path::Combine($new_path, $p . '.' . $n_ext);
                    //update all attached attribute 
                    foreach ($this->m_auto_resolution[$uri] as $p) {
                        $p->target[$p->attrName] = $v;
                    }
                    $ext = $n_ext;
                }
                // + | resolve module fields content 
                $v_hostn ='/';
                $v_baseuri = IGKValidator::IsUri($k) ? HttpUtility::GetBaseUri($k) : $this->getBaseUri();
                $v_baseisuri = IGKValidator::IsUri($v_baseuri);
                if ($v_baseisuri){
                    $v_hostn = $v_baseuri;
                }
                if ($v_baseisuri && IGKValidator::IsUri($k)){
                    if (($this->getBaseHost() != HttpUtility::GetBaseHost($k))){
                        $v_hostn = HttpUtility::GetBaseUri($k);
                        if ($v_path = igk_getv(parse_url($k), 'path')){                            
                            $v_hostn .= '/'.ltrim(dirname($v_path), '/');
                        }
                    }
                } else {
                    if ($v_baseisuri)
                        $v_hostn .= '/'.ltrim(dirname($k), '/');
                }
                if (isset($this->m_module_js[$k])){
                    $content = $this->resolveJSModuleContent($content, $res, $v_hostn, $new_path);
                    $content = self::_UpdateSourceMap($this, $res, $v_hostn, $content, null, $new_path, null);
                    $v_update_sourcemap = true;
                } 
                if ($ext == 'css') {
                    // if is css treat - content - 
                    $content = self::TreatCssContent($this, $content, $res, $new_path);
                    $buri = dirname(explode('?', $uri, 2)[0]);
                    if ($baseHost == igk_getv(parse_url($uri), 'host')) {
                        $new_path = '/';
                    }
                    $content = self::_UpdateSourceMap($this, $res, $buri, $content, null, $new_path, null);
                    $v_update_sourcemap = true;
                }
                if (!$v_update_sourcemap && in_array($ext, ['js','css'])){
                    $content = self::_UpdateSourceMap($this, $res, $v_hostn, $content, null, $new_path, null);
                }
                $v = ltrim($v, '/');
                $this->_store($v, $content);
                if ($ext == 'html') {
                    if (($this->m_maxLevel > 0) && (($this->level + 1) >= $this->m_maxLevel)) {
                        continue;
                    }
                    $doc = new static;
                    $doc->base = Path::CombineAndFlattenPath($this->getBaseUri(), $new_path);
                    $doc->m_maxLevel = $this->m_maxLevel;
                    $doc->m_resources = &$this->m_resources;
                    $doc->m_load_res = $k;
                    $doc->m_downloads = &$this->m_downloads;
                    $doc->m_httpClient = $this->m_httpClient;
                    $doc->m_res_index = $v_count;
                    $doc->m_isSource = false;
                    $doc->skipAutoIndex = true;
                    $doc->level = $doc->level + 1;
                    $doc->parseContent($content);
                    $doc->exportTo($outdir);
                }
            } else {
                Logger::danger('failed: ' . $status);
            }
        }
        if (!$this->skipAutoIndex) {
            $this->_store($v_outindex, $this->render());
        }
        // $this->m_resources = array_merge($this->m_resources, ["[--download--]"], $downloads);
        if ($sm_initiator === $this) {
            $sm_initiator = null;
            $sm_shared_download = null;
        }
        chdir($bck);
    }
    private function _request($uri)
    {
        $this->m_httpClient->followLocation = true;
        $this->m_httpClient->base = $this->base;
        if (IGKValidator::IsUri($uri)) {
            $host = $this->getBaseHost();
            $thost = HttpUtility::GetBaseHost($uri);
            if ($host == $thost) {
                $uri = igk_getv(parse_url($uri), 'path');
            }
        }
        return $this->m_httpClient->request($uri);
    }
    /**
     * store file
     * @param string $file 
     * @param string $content 
     * @return void 
     * @throws IGKException 
     */
    private function _store(string $file, string $content)
    {
        Logger::info("store: " . $file);
        igk_io_w2file($file, $content);
    }
    /**
     * get uri hash
     * @param mixed $file file to hash
     * @param ?string $extra extra file to hash
     * @return string retrieve file name with hash 
     */
    protected function getFileUriHash($file, ?string $extra, string $type = "css")
    {
        $this->getBaseHost();
        $hash = '';
        if (!empty($extra)) {
            $hash = '-' . hash($this->hashAlogithm, $extra);
        }
        $nfile = $file;
        $nfile = dirname($nfile);
        if ($nfile=='.'){
            $nfile = './';
        } else if ($nfile=='/'){
            $nfile = '';
        }

        if (igk_io_path_ext($file) != $type) {
            $nfile = $file . $hash . '.' . $type;
        } else if ($hash) {
            $nfile = sprintf(
                '%s/%s%s.%s',
                $nfile,
                igk_io_basenamewithoutext($file),
                $hash,
                $type
            );
        }
        return $nfile;
    }
}
