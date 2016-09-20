<?php
require_once 'lib/picasa/autoload.php';
// $cacher = new Doctrine\Common\Cache\ArrayCache();
session_start();
$cacher = new Doctrine\Common\Cache\FilesystemCache('/tmp');


$albumName=$_SESSION['picasaAlbum'];

$img=$_SESSION['picasaImg'];

$uploader = RemoteImageUploader\Factory::create('Picasa', array(
    'cacher'         => $cacher,
    'api_key'        => '98076226649-5kuqs6muv780l5l6thdmo1sdkdl0t6rq.apps.googleusercontent.com',
    'api_secret'     => '2xOZw10g68p2VUqvs4R3rP-l',

    // if `album_id` is `null`, this script will automatic
    // create a new album for storage every 2000 photos
    // (due Google Picasa's limitation)
    'album_id'               => null,
    'auto_album_title'       => $albumName,
    'auto_album_access'      => 'public',
    'auto_album_description' => 'App created by sagar bhatt',

    // if you have `refresh_token` you can set it here
    // to pass authorize action.
    'refresh_token' => null,
));

$callbackUrl = 'http'.(getenv('HTTPS') == 'on' ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
//echo var_dump($callbackUrl);
//echo '<script> alert('.var_dump($callbackUrl).');</script>';
$uploader->authorize($callbackUrl);

//$url = $uploader->upload('/Volumes/Data/Data/Photos/My Icon/ninja.JPG');
//var_dump($url);

// http://dantri.vcmedia.vn/Uploaded/2011/04/08/9f5anh%205.JPG
$filename = [];
foreach($img as $file){
$url = $uploader->transload($file);
array_push($filename,$url);
}


$filename=array($filename);


$_SESSION['picasaUpload'] =$filename;

echo "<script>alert('Album successfully uploaded')</script>";


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
    <h2>Please check your picasa album</h2>
    
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
<script>
 $(function(){//document.ready shortcut
  
   setTimeout(function(){window.close();},3000);//timeout code to close window
  
 });
</script>
<script src="lib/js/jquery.min.js"></script>
<script src="lib/bootstrap/js/bootstrap.min.js" ></script>

</body>

</html>
