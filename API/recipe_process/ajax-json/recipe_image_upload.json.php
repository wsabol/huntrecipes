<?php

$errors = array();
$sFilename = "";

/******** FILE UPLOAD *********/
if ( isset($_FILES['rImageFile']) ) {
  
  $file_name = $_FILES['rImageFile']['name'];
  $file_size = $_FILES['rImageFile']['size'];
  $file_tmp = $_FILES['rImageFile']['tmp_name'];
  $file_type = $_FILES['rImageFile']['type'];
  /* change the filename with a customized filename.
  Here the filename is generated by the combination of system time and hostname of the users’ system */
  $sFilename = date("Ymdhis").gethostbyaddr($_SERVER["REMOTE_ADDR"]).$file_name;

  // create a whitelist of the extensions
  $allowedExts = array("gif", "jpeg", "jpg", "png");
  $allowedTypes = array("image/gif", "image/jpeg", "image/jpg", "image/pjpeg", "image/x-png", "image/png");

  // obtain the extension of the uploaded file and storeit in a variable
  $extension = @strtolower(end(explode('.', $file_name)));

  //echo finfo_file(finfo_open(FILEINFO_MIME_TYPE), $_FILES["file"]["tmp_name"]);
  // validate the content-type header of uploaded file
  if (
      in_array($file_type, $allowedTypes)
      && $file_size < 1000000000
      // validate file extension
      && in_array($extension, $allowedExts)
      // check file content and derive the actual content-type, the validate it with the allowed content-types
      && in_array(finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file_tmp), $allowedTypes)
    ) {

    if ($_FILES['rImageFile']['error'] > 0) {
      array_push($errors, "Error: " . $_FILES['rImageFile']['error']);
    }
    else {
      //echo getcwd();
      while ( file_exists("/home/customer/www/huntrecipes.willsabol.com/public_html/assets/images/recipes/".$sFilename) ) {
        $sFilename = "A".$sFilename;
      }

      // move file to a permanent storage in the file system
      if ( !move_uploaded_file( $file_tmp, "/home/customer/www/huntrecipes.willsabol.com/public_html/assets/images/recipes/".$sFilename ) ) {
        array_push($errors, 'Failed to move to "assets/images/recipes" folder');
      }

    }
  }
  else {
    array_push($errors, "Invalid file. JPG, PGN, & GIF are allowed.");
  }
  
} else {
  array_push($errors, "Posted file not found.");
}

if ( empty($errors) == true ) {
	chmod("/home/customer/www/huntrecipes.willsabol.com/public_html/assets/images/recipes/".$sFilename , 0644);
}

echo json_encode( array( 'image_filename'=>$sFilename, 'errors'=>$errors ) );

?>