<?php
use PHPUnit\Framework\TestCase;
require_once  'lib/_includes/fbsdk/src/Facebook/autoload.php';

class fbTest extends TestCase
{


    public function testConfig()
    {
 
        
        require 'fbConfig.php';
        
        $this->assertNotEmpty($app_id);
        $this->assertNotEmpty($app_sec);
        
        $this->assertEquals($g_v,'v2.7');
        
    }

   public function testCallbackUrl(){

        require 'fbConfig.php';
        $this->assertEquals($callBack,'http://sagarkbhatt.me/login_callback.php');  
    }


}

?>
