<?php

require '../vendor/autoload.php';
// $cacher = new Doctrine\Common\Cache\ArrayCache();
$cacher = new Doctrine\Common\Cache\FilesystemCache('/tmp');

$uploader = RemoteImageUploader\Factory::create('Imgur', array(
    'cacher' => $cacher
));

$url = $uploader->upload('/Volumes/Data/Data/Photos/My Icon/ninja.JPG');
var_dump($url);

$url = $uploader->transload('http://s26.postimg.org/f0lrm6vqh/ninja.jpg');
var_dump($url);