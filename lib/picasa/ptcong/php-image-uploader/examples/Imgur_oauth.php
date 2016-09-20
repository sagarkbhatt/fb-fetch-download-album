<?php

require '../vendor/autoload.php';
// $cacher = new Doctrine\Common\Cache\ArrayCache();
$cacher = new Doctrine\Common\Cache\FilesystemCache('/tmp');

$uploader = RemoteImageUploader\Factory::create('Imgur', array(
    'cacher'         => $cacher,
    'api_key'        => 'your API client id',
    'api_secret'     => 'your API client secret',

    // if you have `refresh_token` you can set it here
    // to pass authorize action.
    // 'refresh_token' => '',

    // If you don't want to authorize by yourself, you can set
    // this option to `true`, it will requires `username` and `password`.
    // But sometimes Imgur requires captcha for authorize so this option
    // will be failed. And you need to set it to `false` and do it by
    // yourself.
    'auto_authorize' => false,
    'username'       => 'your user name',
    'password'       => 'your password'
));

$uploader->authorize();

$url = $uploader->upload('/Volumes/Data/Data/Photos/My Icon/ninja.JPG');
var_dump($url);

$url = $uploader->transload('http://s26.postimg.org/f0lrm6vqh/ninja.jpg');
var_dump($url);