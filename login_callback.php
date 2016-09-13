<?php

    session_start();
    require_once __DIR__ . '/_includes/fbsdk/src/Facebook/autoload.php';


    $fb = new Facebook\Facebook([
    'app_id' => '1375334972496509', // Replace {app-id} with your app id
    'app_secret' => '55cc4917fae02dcfe988a6a97c562a02',
    'default_graph_version' => 'v2.7',
    ]);

    $helper = $fb->getRedirectLoginHelper();
    try {
    $accessToken = $helper->getAccessToken();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
    }

    if (isset($accessToken)) {
    // Logged in!
    $_SESSION['facebook_access_token'] = (string) $accessToken;

    // Now you can redirect to another page and use the
    // access token from $_SESSION['facebook_access_token']
    header('Location:Main.html');

    }


?>