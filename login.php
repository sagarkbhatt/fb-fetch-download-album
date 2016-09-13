<?php

    session_start();
    require_once __DIR__ . '/_includes/fbsdk/src/Facebook/autoload.php';
    
    $fb = new Facebook\Facebook([
    'app_id' => '1375334972496509', // Replace {app-id} with your app id
    'app_secret' => '55cc4917fae02dcfe988a6a97c562a02',
    'default_graph_version' => 'v2.7',
    ]);

    $helper = $fb->getRedirectLoginHelper();
    $permissions = ['email', 'user_likes']; // optional
    $loginUrl = $helper->getLoginUrl('http://localhost/sagarkbhatt.github.io/login_callback.php', $permissions);

    echo '<a href="' . $loginUrl . '">Log in with Facebook!</a>'
?>