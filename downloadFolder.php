<?php

$errors = array();
$data = array();

$postdata = file_get_contents('php://input');
$request = json_decode($postdata);
//var_dump($request);
//echo $request;
$zip = new ZipArchive();
$filename = 'uploads/'.time().'.zip';
if($zip->open($filename, ZipArchive::CREATE)!=TRUE)
    die ("Could not open archive");

$temp=[];
foreach($request as $k){

$img=$k->img;
$albumName=$k->name;


foreach($img as $file){
	
	$download_file = file_get_contents($file);
	
	$name =basename($file);
	array_push($temp,$name);
	file_put_contents($name,$download_file);
	if(!isset($albumName))
	$zip->addFile($name);
	else
	$zip->addFile($name,$albumName.'/'.$name);

	
	
	
}

}
$zip->close();
//echo 'Exist or not'.file_exists($filename);

foreach($temp as $n){
	
	unlink($n);
}



$filename=array($filename);
echo json_encode($filename);
# close zip


?>