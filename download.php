<?php

$errors = array();
$data = array();
// Getting posted data and decodeing json
$postdata = file_get_contents('php://input');
$request = json_decode($postdata);
$img = $request;



//echo $img;

$zip = new ZipArchive();

$filename = 'uploads/'.time().'.zip';
if($zip->open($filename, ZipArchive::CREATE)!=TRUE)
    die ("Could not open archive");
$temp=[];
foreach($img as $file){

    # download file
    $download_file = file_get_contents($file);
    #add it to the zip
    $name =basename($file);
    array_push($temp,$name);
    file_put_contents($name,$download_file);

    if (!file_exists($name)) { die($name.' does not exist'); }
      if (!is_readable($name)) { die($name.' not readable'); }
    $zip->addFile($name);

    
    
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