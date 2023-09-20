<?php
// @author: C.A.D. BONDJE DOUE
// @filename: WebScrapperTest.php
// @date: 20230919 18:04:59
// @desc: 
// @command: phpunit -c phpunit.xml.dist ./src/application/Packages/Modules/igk/tools/webscrapper/Lib/Tests/WebScrapperTest.php

namespace igk\tools\webscrapper\Tests;

use IGK\Helper\IO;
use igk\tools\webscrapper\Tests\ModuleTestBase;
use igk\tools\webscrapper\WebScrapperDocument;
require_once __DIR__ .'/ModuleTestBase.php';

class WebScrapperTest extends ModuleTestBase{

    public function test_scrap_content(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><title>document</title></head></html>';
        if ($document->parseContent($t)){
            $title = $document->getTitle();
            $this->assertEquals('document', $title);
        } else {
            $this->fail('failed parseContent');
        }
    }
    public function test_scrap_script_url(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><script type="javascript" src="assets/js/main.js"></script></head></html>';
        if ($document->parseContent($t)){
            $this->assertEquals(count($document->scripts()),1);
        } else {
            $this->fail('failed parseContent');
        }
    }
    public function test_scrap_script_img(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head></head><body><img src="assets/img/favicon.ico" /></body></html>';
        if ($document->parseContent($t)){
            $this->assertEquals(count($document->images()),1);
        } else {
            $this->fail('failed parseContent');
        }
    }
    public function test_scrap_link(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><link rel="stylesheet" href="assets/css/main.css" /></head><body><img src="assets/img/favicon.ico" /></body></html>';
        if ($document->parseContent($t)){
            $this->assertEquals(count($document->links()),1);
        } else {
            $this->fail('failed parseContent');
        }
    } 



    public function test_scrap_script_inner_style(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><style>@import "/main.css";</style></head><body></body></html>';
        if ($document->parseContent($t)){
            $this->assertEquals($t,$document->render());
        } else {
            $this->fail('failed parseContent');
        }
    } 
    public function test_scrap_script_inner_style_with_query(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><style>@import "main.css?query";</style></head><body></body></html>';
       igk_debug(1);
        if ($document->parseContent($t)){
            $this->assertEquals('<!DOCTYPE html><html><head><style>@import "/main-a671562d.css";</style></head><body></body></html>',
            $document->render());

            $this->assertEquals([
                "/main.css?query"=>"/main-a671562d.css"
            ], $document->resources());
        } else {
            $this->fail('failed parseContent');
        }
    } 
    public function test_scrap_script_inner_style_with_absolute_and_query(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><style>@import "https://local.com/assets/main.css?query";</style></head><body></body></html>';
        $document->base = 'https://local.com';
        if ($document->parseContent($t)){
            $this->assertEquals('<!DOCTYPE html><html><head><style>@import "/assets/main-a671562d.css";</style></head><body></body></html>',
            $document->render());

            $this->assertEquals([
                "/assets/main.css?query"=>"/assets/main-a671562d.css"
            ], $document->resources());
        } else {
            $this->fail('failed parseContent');
        }
    } 

    public function test_scrap_script_inner_style_with_url_path(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><style>body{background-image:url(assets/img/logo.jpg) } p{background-image:url(https://local.com:7300/assets/img/logo.jpg) }</style></head><body></body></html>';
        $document->base = 'https://local.com:7300';
        if ($document->parseContent($t)){
            $this->assertEquals('<!DOCTYPE html><html><head><style>body{background-image:url(/assets/img/logo.jpg) } p{background-image:url(/assets/img/logo.jpg) }</style></head><body></body></html>',
            $document->render());

            $this->assertEquals([
                "/assets/img/logo.jpg"=>"/assets/img/logo.jpg"
            ], $document->resources());
        } else {
            $this->fail('failed parseContent');
        }
    } 

    public function test_detect_child_uri(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><meta charset="UTF-8" /></head><body><a href="/pages/about">about</a></body></html>';
        $document->base = 'https://local.com:7300';
        $document->setHttpClient(new WebScrapperTestHttpClient);
        if ($document->parseContent($t)){
            $this->assertEquals(
                '<!DOCTYPE html><html><head><meta charset="UTF-8"/></head><body><a href="pages/about.html">about</a></body></html>',
                $document->render());
            $temp = igk_io_tempdir('wbs-');
            $document->exportTo($temp);

            // `code {$temp}`;
            
            IO::RmDir($temp);
        } else {
            $this->fail('failed parseContent');
        }
        
    }

    public function test_scrap_link_inline_css_ressources(){
        $document = new WebScrapperDocument;
        $t = '<!DOCTYPE html><html><head><link rel="stylesheet" href="assets/css/main.css" /></head><body></body></html>';
        if ($document->parseContent($t)){
            $this->assertEquals(count($document->links()),1);
            $client = new WebScrapperTestHttpClient;
            $client->data = [
                '/assets/css/main.css'=>'body{background-color:red; background-image:url(info.jpg);'.
                    'p{background-color:url(/logo.jpg);}'.
                    'a{background-color:url(./info.jpg);}',
                '/assets/css/info.jpg'=>'home',
                '/logo.jpg'=>'logo'
            ];

            $tmp = igk_io_tempdir('link-image');
            $document->setHttpClient($client);
            $document->exportTo($tmp);

            $s = file_get_contents($tmp."/assets/css/main.css");
            $this->assertEquals(
                'body{background-color:red; background-image:url(info.jpg);'.
                'p{background-color:url(/logo.jpg);}'.
                'a{background-color:url(./info.jpg);}',
                $s
            );


            $this->assertEquals([
                $tmp.'/index.html',
                $tmp.'/logo.jpg',
                $tmp.'/assets/css/info.jpg',
                $tmp.'/assets/css/main.css',
            ],
            igk_io_getfiles($tmp)
            );
            IO::RmDir($tmp);

        } else {
            $this->fail('failed parseContent');
        }
    }

    public function test_list_ignore_mail_to_a(){
        $document = new WebScrapperDocument;
        $a = IGK_AUTHOR_CONTACT;
        $t = '<!DOCTYPE html><html><body><a href="mailto:'.$a.'"></a></body></html>';
        if ($document->parseContent($t)){
            $this->assertEquals($t, $document->render());
        }
        else 
            $this->fail('parse failed');
    }

    public function test_export_external_resource(){
        // + | --------------------------------------------------------------------
        // + | export external asset
        // + |
        
        $p   = new WebScrapperDocument;
        if ($p->parseContent('<script src="https://unpkg.com/vue-router@4.2.4/dist/vue-router.global.prod.js?format"></script>')){
            $tmp = igk_io_tempdir('ext-image');
           
            $s = $p->render();
            $this->assertEquals('<script src="assets/ext/e70f63ab/vue-router@4.2.4/dist/vue-router.global.prod-deba72df.js"></script>',
             $s);

            $client = new WebScrapperTestHttpClient;
            $client->data = [ 
                '/logo.jpg'=>'logo',
                'https://unpkg.com/vue-router@4.2.4/dist/vue-router.global.prod.js?format'=>'/*vue router*/'
            ];
            $p->setHttpClient($client);
            $p->exportTo($tmp); 

            $this->assertEquals([
                $tmp.'/index.html', 
                $tmp.'/assets/ext/e70f63ab/vue-router@4.2.4/dist/vue-router.global.prod-deba72df.js', 

            ],
            igk_io_getfiles($tmp)
            );
            IO::RmDir($tmp);


        }

    }


    public function test_export_inline_module_js(){
        // + | --------------------------------------------------------------------
        // + | export external asset
        // + |
        
        $p   = new WebScrapperDocument;
        if ($p->parseContent('<script src="assets/js/main.js" type="module"></script><script src="https://blabla.com/assets/js/main.js" type="module"></script>')){
            $tmp = igk_io_tempdir('ext-image');
           
            $s = $p->render();
            $this->assertEquals('<script src="assets/js/main.js" type="module"></script><script src="assets/ext/8cd96547/assets/js/main.js" type="module"></script>',
             $s);

            $client = new WebScrapperTestHttpClient;
            $client->data = [ 
                '/assets/js/main.js'=>'import("./vite.js")',
                '/assets/js/vite.js'=>'console.log("presentation");',
                'https://blabla.com/assets/js/main.js'=>'import("./blabla.js");',
                'https://blabla.com/assets/js/blabla.js'=>'/* .blabla.js */'."\n/*# sourceMappingURL=data.js.map #*/",
                'https://blabla.com/assets/js/data.js.map'=>json_encode([
                    'version'=>3,
                    'sources'=>[],
                    'minmaps'=>[]
                ])
            ];
            $p->setHttpClient($client);
            $p->exportTo($tmp); 
            $b =  igk_io_getfiles($tmp);
            $this->assertEquals([
                $tmp.'/index.html',  
                $tmp.'/assets/js/main.js',  
                $tmp.'/assets/js/vite.js',  
                $tmp.'/assets/ext/8cd96547/assets/js/blabla.js',  
                $tmp.'/assets/ext/8cd96547/assets/js/data.js.map',  
                $tmp.'/assets/ext/8cd96547/assets/js/main.js',  
            ],
           $b
            );
            IO::RmDir($tmp);


        }

    }


    public function test_import_for_js_module(){
        // + | --------------------------------------------------------------------
        // + | export external asset
        // + |
        
        $p   = new WebScrapperDocument;
        if ($p->parseContent('<script type="module" src="/assets/_mod_/igk/js/Vue3/Scripts/default.js"></script>')){
            $tmp = igk_io_tempdir('ext-image');
           
            $s = $p->render();
            $this->assertEquals('<script src="/assets/_mod_/igk/js/Vue3/Scripts/default.js" type="module"></script>',
             $s);

            $client = new WebScrapperTestHttpClient;
            $client->data = [ 
                '/assets/_mod_/igk/js/Vue3/Scripts/default.js'=>'import("./vite.js")',
                '/assets/_mod_/igk/js/Vue3/Scripts/vite.js'=>'/* vite.js */',                 
            ];
            $p->setHttpClient($client);
            $p->exportTo($tmp); 
            $b =  igk_io_getfiles($tmp);
            $this->assertEquals([
                $tmp.'/index.html',  
                $tmp.'/assets/_mod_/igk/js/Vue3/Scripts/default.js',   
                $tmp.'/assets/_mod_/igk/js/Vue3/Scripts/vite.js',   
            ],
           $b
            );
            IO::RmDir($tmp);


        }

    }
}