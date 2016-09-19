<?php

    $app_id='1375334972496509';
    $app_sec='55cc4917fae02dcfe988a6a97c562a02';
    $g_v='v2.7';
    $callBack='http://sagarkbhatt.me/login_callback.php';

    $fb = new Facebook\Facebook([
    'app_id' => $app_id, // Replace {app-id} with your app id
    'app_secret' =>$app_sec ,
    'default_graph_version' =>$g_v 
    
    ]);

?>