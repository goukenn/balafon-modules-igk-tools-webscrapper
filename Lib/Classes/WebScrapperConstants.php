<?php
// @author: C.A.D. BONDJE DOUE
// @file: WebScrapperConstants.php
// @date: 20230914 13:00:06
namespace igk\tools\webscrapper;


///<summary></summary>
/**
* 
* @package igk\tools\webscrapper
*/
abstract class WebScrapperConstants{
    const REF_TAG = 'head|body|link|title|a|script|meta|object|img|video|audio|source|base|style|picture';
    const URL_BRACKET_RX = '(?P<bracket>(\'|"))?(?P<path>(((\.)?\.\/|\/|(?P<protocol>[a-z0-9]+):\/\/)|(?P<inline_data>data):|[a-z0-9]+)[^\'\)\#\?\,\+\: ]+)(?P<extra>([^\) ]+))?(?(bracket)\\%s)';
}