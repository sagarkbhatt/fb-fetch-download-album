<?php


$postdata = file_get_contents('php://input');
$request = json_decode($postdata);

$img=$request->img;
$albumName=$request->name;

session_start();

$_SESSION['picasaAlbum']=$albumName;
$_SESSION['picasaImg']= $img;
$_SESSION['picasaData']=$request;

$op='Data written';
echo json_encode($op);
?>
