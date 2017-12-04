<?php
	session_start();
	include("superGlobals.php");
	include("inc_cfd.php");
	include("functions.php");

	//VARIABLES~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	$target_dir = "../uploads/";
	$imageFileType = pathinfo($_FILES["fileToUpload"]["name"],PATHINFO_EXTENSION);//file type without "."
	$fileToScan = fread(fopen($_FILES["fileToUpload"]["tmp_name"], "r"), filesize($_FILES["fileToUpload"]["tmp_name"]));//the whole image in a string
	$uploadedfile =  $_FILES["fileToUpload"]["size"]; //filesize of file
	$fileTmpName = $_FILES["fileToUpload"]["tmp_name"];
	$rand = uniqid('', true); //random name
	$target_file = $target_dir . $rand . "." . $imageFileType; //new location and random name of file on server
	$file_base = basename($target_file); //new random name of file
	$actualName = basename($_FILES["fileToUpload"]["name"]); //actual name of file
	$imageType = ".".$imageFileType;//File type for SQL
	$size = getimagesize($_FILES["fileToUpload"]["tmp_name"]);//$size[0] = width and $size[1] = height
	$userAgent = xss_sanitize($_POST["userAgent"]); //Sanitize input before evaluating
	$echoMethod = xss_sanitize($_POST["echoMethod"]);
	//VARIABLES~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//check if request is from browser or application
	if ($userAgent == 1){//User is from desktop application (already logged in)
	 	chkimgSupport($imageFileType);
	 	chkMalCode($fileToScan);
	 	chkImgSize($uploadedfile);
	 	fileMove($fileTmpName, $imageFileType, $rand);
	 	insertSQL(0,$file_base,$actualName,$imageType,$size[0],$size[1],$uploadedfile,"N",1,$echoMethod);
	// 	echo "Link: www.cloudflare.com\r\n";
	// }else if ($userAgent == 2){//user is from mobile app
	// 	echo "mobile";
	}else if ($userAgent == 0){

		if(isset($_POST["submit"])) {
			//form is submitted 
			chkimgSupport($imageFileType);
			chkMalCode($fileToScan);
			chkImgSize($uploadedfile);
			fileMove($fileTmpName, $imageFileType, $rand);
			if (isset($_SESSION['user_ID'])){
				insertSQL($_SESSION['user_ID'],$file_base,$actualName,$imageType,$size[0],$size[1],$uploadedfile,"N",0);
			}else{
				insertSQL(0,$file_base,$actualName,$imageType,$size[0],$size[1],$uploadedfile,"N",0);
			}
		}else{//form wasn't submitted
			header("Location: index.php");
			exit();
		}
	}else{
		header("Location: index.php");
	}
?>
