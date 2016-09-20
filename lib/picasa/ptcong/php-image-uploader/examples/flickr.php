<?php

require '../vendor/autoload.php';
// $cacher = new Doctrine\Common\Cache\ArrayCache();
$cacher = new Doctrine\Common\Cache\FilesystemCache('/tmp');

$uploader = RemoteImageUploader\Factory::create('Flickr', array(
    'cacher'         => $cacher,
    'api_key'        => 'your api key',
    'api_secret'     => 'your api secret',

    // if you have oauth_token and secret, you can set
    // to the options to pass
    'oauth_token'        => null,
    'oauth_token_secret' => null,
));

$callbackUrl = 'http'.(getenv('HTTPS') == 'on' ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$uploader->authorize($callbackUrl);

$url = $uploader->upload('/Volumes/Data/Data/Photos/My Icon/ninja.JPG');
var_dump($url);

$url = $uploader->transload('http://s26.postimg.org/f0lrm6vqh/ninja.jpg');
var_dump($url);
