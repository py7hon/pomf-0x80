<?php

header("Access-Control-Allow-Origin: *");

require('Settings.php');
require('Func.php');
require('IPFS.php');

$ipfs = new IPFS(POMF_IPFS, POMF_IPFS_PORT, POMF_IPFS_PORT_API);

$mimeTypes = BLOCKED_MIME;

if(isset($_FILES["files"])) {
	$string = generateRandomString();
	$fileError = $_FILES["files"]["error"];
	switch($fileError) {
    case UPLOAD_ERR_INI_SIZE:
        echo "The uploaded file exceeds the upload_max_filesize directive in php.ini"; 
        break;
    case UPLOAD_ERR_FORM_SIZE:
        echo "The uploaded file exceeds the MAX_FILE_SIZE directive that was ". 
             "specified in the HTML form";
        break;
    case UPLOAD_ERR_PARTIAL:
        echo "The uploaded file was only partially uploaded";
        break;    
    case UPLOAD_ERR_NO_FILE:
        echo "No file was uploaded";
        break;
    case UPLOAD_ERR_NO_TMP_DIR:
        echo "Missing a temporary folder";
        break;
    case UPLOAD_ERR_CANT_WRITE:
        echo "Failed to write file to disk";
        break;
    case UPLOAD_ERR_EXTENSION:
        echo "File upload stopped by extension";
        break;
    default:
        echo "Unknown upload error";
        break;
  }
	if(in_array($_FILES["files"]["type"], $mimeTypes)){
		http_response_code(415);
    throw new Exception('File type not allowed!', 415 );
    exit(0);
  }else{
		//trying to upload image to IPFS
		$item = file_get_contents($_FILES["files"]["tmp_name"]);
		$hash = $ipfs->add($item);
		//pin files
		$pin = $ipfs->pinAdd($hash);
		$cid = $hash;
		
		if(isset($cid) && !empty($cid)){
		  $ext = pathinfo($_FILES["files"]['name'], PATHINFO_EXTENSION);
		  $filename = $string . '.' . $ext;
		  $pomfurl = POMF_URL;
		  move_uploaded_file($_FILES["files"]["tmp_name"], POMF_FILES_ROOT . $filename);
		  $response_type = isset($_GET['output']) ? $_GET['output'] : 'json';
		  switch($response_type){
			   case 'html':
			     header('Content-Type: text/html; charset=UTF-8');
			     echo '<a href="'.$pomfurl.'/'.$filename.'">'.$pomfurl.'/'.$filename.'</a><br>';
			     echo '<a href="https://ipfs.io/ipfs/'.$cid.'">https://ipfs.io/ipfs/'.$cid.'</a>';
			     break;
			   case 'json':
			     header('Content-Type: application/json; charset=UTF-8');  
			     $result = array(
				     'success' => true,
				     'name' => $_FILES["files"]['name'],
				     'files' => array(
					       $pomfurl.'/'.$filename,
					       'https://ipfs.io/ipfs/'.$cid
				     )
				   );
				   $result = json_encode($result);
				   echo $result;
				   break;
		  	case 'text':
			  	 header('Content-Type: text/plain; charset=UTF-8');
			  	 echo $pomfurl.'/'.$filename;
			  	 echo "\n";
			  	 echo 'https://ipfs.io/ipfs/'.$cid;
			  	 break;
		  	default:
			  	 header('Content-Type: application/json; charset=UTF-8');
			  	 $result = array(
			  	   'success' => false,
			  	   'message' => 'Invalid response type. Valid options are: csv, html, json, text.'
			  	 );
			  	 $result = json_encode($result);
			  	 echo $result;
			  	 break;
		  }
	  }else{
		  	//creating error result output
		  	header('Content-Type: application/json; charset=UTF-8');
		  	$result = array(
		  		'success' => false,
		  		'name' => $_FILES["files"]['name'],
		  		'message' => 'Sorry, there was an error uploading your file.'
			  	);
			  echo json_encode($result);
		  }
  }
}else{
  header('Content-Type: application/json; charset=UTF-8');
  $result = array(
    'success' => false,
    'errorcode' => 400,
    'description' => 'No input file(s)'
  );
  echo json_encode($result);
}
?>
