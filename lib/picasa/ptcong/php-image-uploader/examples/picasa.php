<?php

require '../vendor/autoload.php';
// $cacher = new Doctrine\Common\Cache\ArrayCache();
$cacher = new Doctrine\Common\Cache\FilesystemCache('/tmp');

$uploader = RemoteImageUploader\Factory::create('Picasa', array(
    'cacher'         => $cacher,
    'api_key'        => 'your client id',
    'api_secret'     => 'your client secret',

    // if `album_id` is `null`, this script will automatic
    // create a new album for storage every 2000 photos
    // (due Google Picasa's limitation)
    'album_id'               => null,
    'auto_album_title'       => 'Auto Album %s',
    'auto_album_access'      => 'public',
    'auto_album_description' => 'Created by Remote Image Uploader',

    // if you have `refresh_token` you can set it here
    // to pass authorize action.
    'refresh_token' => null,
));

$callbackUrl = 'http'.(getenv('HTTPS') == 'on' ? 's' : '').'://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];

$uploader->authorize($callbackUrl);

$url = $uploader->upload('/Volumes/Data/Data/Photos/My Icon/ninja.JPG');
var_dump($url);

// http://dantri.vcmedia.vn/Uploaded/2011/04/08/9f5anh%205.JPG
$url = $uploader->transload('http://s26.postimg.org/f0lrm6vqh/ninja.jpg');
var_dump($url);
