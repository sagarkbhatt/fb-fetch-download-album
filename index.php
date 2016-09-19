<?php

    if(!session_id()) {
    session_start();
    }
    
    //session_start();
    require_once  'lib/_includes/fbsdk/src/Facebook/autoload.php';
    
    /*$fb = new Facebook\Facebook([
    'app_id' => '1375334972496509', // Replace {app-id} with your app id
    'app_secret' => '55cc4917fae02dcfe988a6a97c562a02',
    'default_graph_version' => 'v2.3'
    
    ]);*/
    require_once 'fbConfig.php';


    $helper = $fb->getRedirectLoginHelper();
    $permissions = ['email', 'user_photos','user_videos','user_about_me','user_posts','public_profile']; // optional
    $loginUrl = $helper->getLoginUrl($callBack, $permissions);
 
    session_write_close();    
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="author" content="SagarBhatt">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="">

    <title>Facebook Album</title>
    <link rel="stylesheet" href="lib/bootstrap/css/bootstrap.min.css" />
    <link rel="stylesheet" href="lib/css/app.css" />
    <link rel="stylesheet" href="lib/css/style.css" />
    <link rel="stylesheet" href="lib/bootstrap_social/bootstrap-social.css" />
    <link rel="stylesheet" href="lib/bootstrap_social/assets/css/font-awesome.css"/>
   <style>

   </style>

</head>

<body>

<div class="container content">
<div class="jumbotron">
    <h1>Sagar Bhatt<small> (14BIT152) </small></h1>
    <h3>Institute of technology,Nirma University</h3>
</div>
<div class="jumbotron">
   <?php echo' <a  href="'.$loginUrl . '" class="btn btn-block btn-social btn-facebook" >';
   echo '<i class="fa fa-facebook"></i>';
   echo ' Sign in with Facebook';
   echo ' </a> '; ?>
    
</div> 
<div class="jumbotron">
    <h2>PHP Web Developer Assignments</h2>
    <h3>Facebook-Album Challenge</h3>
</div>
</div>


<nav class="navbar navbar-inverse navbar-fixed-bottom">
    <div class="container-fluid">
        <div class="navbar-header">

            <a href="#" class="navbar-brand">
                <span class="glyphicon glyphicon-copyright-mark">Developed By SagarBhatt</span>
            </a>
            </p>
        </div>

    </div>
</nav>

<script src="lib/js/jquery.min.js"></script>
<script src="lib/bootstrap/js/bootstrap.min.js" ></script>

</body>

</html>