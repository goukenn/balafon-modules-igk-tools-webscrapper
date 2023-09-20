<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperCssUrlDetectorTest.php
// @date: 20230914 15:47:27
namespace igk\tools\webscrapper;

use IGK\Tests\Controllers\ModuleBaseTestCase;

///<summary></summary>
/**
* 
* @package igk\tools\webscrapper
*/
class WebScrapperCssUrlDetectorTest extends ModuleBaseTestCase{
    public function test_detect_url_with_no_bracket(){
        $p = new WebScrapperCssUrlDetector;        
        $this->assertEquals(
            ['assets/img.jpg']
            ,$p->match("body{background-color: url(   assets/img.jpg  )}"));
    }
    public function test_detect_url_with_quotes(){
        $p = new WebScrapperCssUrlDetector;        
        $this->assertEquals(
            ['assets/img.jpg']
            ,$p->match("body{background-color: url('assets/img.jpg')}"));

        $this->assertEquals(
            ['assets/img.jpg']
            ,$p->match("body{background-color: url(\"assets/img.jpg\")}"));
    }

    public function test_detect_more_url(){
        $p = new WebScrapperCssUrlDetector;        
        $this->assertEquals(
            ['assets/img.jpg', 'assets/img/photo.jpg']
            ,$p->match("body{background-color: url('assets/img.jpg')} p{ background-image: url('assets/img/photo.jpg')}"));
 
    }
    public function test_detect_missing_close_bracket(){
        $p = new WebScrapperCssUrlDetector;        
        $this->assertEquals(
            ['assets/img.jpg']
            ,$p->match("body{background-color: url('assets/img.jpg'} p{ background-image: url('assets/img/photo.jpg')}"));
 
    }
    public function test_detect_cssUrl(){
        $p = new WebScrapperCssUrlDetector;        
        $xtab = $p->cssUrl("body{background-color: url('assets/img.jpg')} p{ background-image: url('assets/img/photo.jpg')}");
        $this->assertEquals(
            ['assets/img.jpg', 'assets/img/photo.jpg']
            ,$xtab);
 
    }

    
}