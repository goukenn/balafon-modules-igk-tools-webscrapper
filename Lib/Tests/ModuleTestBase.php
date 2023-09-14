<?php
// @author: C.A.D. BONDJE DOUE
// @date: 20230914 12:43:10
namespace igk\tools\webscapper\Tests;

use IGK\Tests\BaseTestCase;

///<summary></summary>
/**
* 
* @package igk\tools\webscapper\Tests
*/
abstract class ModuleTestBase extends BaseTestCase{
	public static function setUpBeforeClass(): void{
	   igk_require_module(\igk\tools\webscapper::class);
	}
}