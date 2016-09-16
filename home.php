<?php
  
    if(!session_id()) {
    session_start();
    }
    
    //session_start();
    require_once __DIR__ . '/lib/_includes/fbsdk/src/Facebook/autoload.php';
    $session=$_SESSION['facebook_access_token'];
    //echo $session;


    $fb = new Facebook\Facebook([
    'app_id' => '1375334972496509', // Replace {app-id} with your app id
    'app_secret' => '55cc4917fae02dcfe988a6a97c562a02',
    'default_graph_version' => 'v2.7',
    'persistent_data_handler'=>'session'
    ]);


    $fb->setDefaultAccessToken($session);

    try {
    $response = $fb->get('/me/albums?fields=cover_photo,photo_count,photos{link,images},picture{url}');
   // $userNode = $response->getGraphUser();
    } catch(Facebook\Exceptions\FacebookResponseException $e) {
    // When Graph returns an error
    echo 'Graph returned an error: ' . $e->getMessage();
    exit;
    } catch(Facebook\Exceptions\FacebookSDKException $e) {
    // When validation fails or other local issues
    echo 'Facebook SDK returned an error: ' . $e->getMessage();
    exit;
    }

     //$get_data = $response->getDecodedBody();
     $graphEdge = $response->getGraphEdge()->AsArray();
     //$ob = $response -> getGraphObject() -> AsArray();
     

     echo json_encode($graphEdge);

     //echo $obj;

    //echo $graphEdge;



?>