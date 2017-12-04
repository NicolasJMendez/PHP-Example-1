<?php
	//Function for checking if file is a supported MIME type
	function chkimgSupport($filetype){
		$files = array('tiff', 'jpeg','png','bmp','gif','jpg', 'JPG', 'GIF', 'PNG', 'BMP' );
		if(in_array($filetype, $files)) {//Check if image file is a actual image or fake image.
			//good
			return;
		}
		else {
			//file not an image
			addError("image type", $filetype." is not supported");
	    	header("Location: error.php?error=file_not_image");
	    	exit();
	    }
	}
	//Function for checking if there are any php tags (for code injection)
	function chkMalCode($fileToScan){
		$needle = "<?php ";
		if (strpos($fileToScan, $needle) == false) {//Check if any malicious code is in image
			//good
			return;
		}else{
			//there was malicious code
			header("Location: error.php?error=malicious_file");
			exit();
		}
	}
	//Function for checking if image is bigger than the maxUploadSize, set in superGlobals
	//This is a secondary check system provided that the user get's past our front-end check
	function chkImgSize($uploadedfile){//check if upload is under specified size
		if ($uploadedfile <= maxUploadSize) {
			//under specified size
			return;
		}else{
			//over specified size
			header("Location: error.php?error=upload_max_limit");
			exit();
		}
	}
	//Move file type from temp to permanent storage based on logged in user or a user with no account
	function fileMove($fileTmpName, $imageFileType, $rand){
		if (isset($_SESSION['username'])){
			
			$target_dir = "uploads/".$_SESSION['username']."/";
		}else{
			$target_dir = "uploads/";
		}
		
		$target_file = $target_dir . $rand . "." . $imageFileType;
		if (move_uploaded_file($fileTmpName, $target_file) == true){
		//if file was moved from temporary storage to permanent storage without any errors
			return;
		}else{
			//failed to move
			addError("Failed move", "failed to move ".$fileTmpName." to server");
			header("Location: error.php?error=move_to_file_error");
			exit();
		}
	}
	//Function to store image within the database using prepared statements 
	//This also marks down how long until the image is flagged for removal by a cron job.
	function insertSQL($user, $filetmpName, $fileName, $fileExtention, $fileWidth, $fileHeight, $fileSize, $isRemoved, $userAgent, $echoMethod){
		include("inc_cfd.php");
		$sqluser_ID = $user;
		$sqlfileTmpName = $filetmpName;
		$sqlfileName = $fileName;
		$sqlfileExtension = $fileExtention;
		$sqlfileWidth = $fileWidth;
		$sqlfileHeight = $fileHeight;
		$sqlfileSize = $fileSize;
		 //DATE_ADD(NOW(), INTERVAL 7 DAY
		$sqlisRemoved = $isRemoved;
		$sqluserAgent = $userAgent;
		$stmt = $conn->prepare("INSERT INTO file 
				(user_ID, fileTmpName, fileName, fileExtension, fileWidth, fileHeight, fileSize, fileUploadedBy, fileExpireDate, isRemoved, userAgent) 
			 	VALUES 
			 	(?,?,?,?,?,?,?,NOW(),DATE_ADD(NOW(), INTERVAL 15 DAY),?,?)");
		//DATE_ADD(NOW(), INTERVAL 15 DAY)
		if (!$stmt->bind_param("ssssiiisi", $sqluser_ID, $sqlfileTmpName, $sqlfileName, $sqlfileExtension, $sqlfileWidth, $sqlfileHeight, $sqlfileSize,  $sqlisRemoved, $sqluserAgent)){
			addError("Binding parameters failed", $stmt->error);
			echo $stmt->error;
			//header("Location: ../error.php?error=genaric_error");
		}
		if (!$stmt->execute()) {
			addError("Execute failed", $stmt->error);
			echo $stmt->error;
			//header("Location: ../error.php?error=genaric_error");
		}
		if ($userAgent == 0){
			if (isset($_SESSION['username'])) {
				header("Location: image-panel.php?file=".$sqlfileTmpName."&username=".$_SESSION['username']);
			}else{
				header("Location: image-panel.php?file=".$sqlfileTmpName."&username=no");
			}
			
		}else if ($userAgent == 1){
			 if ($echoMethod == 0) {
			 	echo "Link: ".root_Dir."image-panel.php?file=".$filetmpName."\r\n";
			 }else{
			 	echo "Link: ".root_Dir."uploads/".$filetmpName."\r\n";
			 }
			
		}else if ($userAgent == 2){
			header("Location: image-panel.php?file=".$sqlfileTmpName);
		}
	}

	//Function to prevent cross site scripting attacks
	function xss_sanitize($string, $removeTags = true) { 
	    if($removeTags) {
	        $string = strip_tags($string); 
	    }
	    $string = str_remove("javascript:", $string); 
	    return htmlEntities($string, ENT_QUOTES, "utf-8"); 
	}

	//Function to sanitize data for SQL storage
	function SanitizeData($str) {
	    if (is_array($str)) {
	        return array_map('_clean', $str);
	    } else {
	        $str = strip_tags($str);
	        return str_replace('\\', '\\\\', trim(htmlspecialchars((get_magic_quotes_gpc() ? stripslashes($str) : $str), ENT_QUOTES)));
	    }
	}

	//Function for string removal, not sure why I did this.
	function str_remove($remove, $haystack) { 
    	return str_replace($remove, "", $haystack); 
	}

	//Fnction to email, not yet set up
	function email($to){
		include('mail/PHPMailerAutoload.php');
		$mail = new PHPMailer;
		 // $mail->SMTPDebug = 4;                               // Enable verbose debug output

		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = 'localhost';  // Specify main and backup SMTP servers

		$mail->setFrom('xxxxxxxxxxxxx', 'Mailer');
		$mail->addAddress( $to , 'Mr. Noreply');     // Add a recipient
		$mail->addReplyTo('xxxxxxxxxxxx', 'Information');
		$mail->Username = 'xxxxxxxxxx';                  // SMTP username
		$mail->Password = 'xxxxxxxx';

		$mail->isHTML(true);                                  // Set email format to HTML

		$mail->Subject = 'test';
		$mail->Body    = 'This is the HTML message body <b>in bold!</b>';
		$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

		if(!$mail->send()) {
		    echo 'Message could not be sent.';
		    echo 'Mailer Error: ' . $mail->ErrorInfo;
		} else {
		    echo 'Message has been sent';
		}
	}

	//Function to log errors to later be displayed on an Admin Error Logging page
	function addError($name, $value){
		include("inc_cfd.php");
		$sql = "INSERT INTO error_log (errorName, errorValue)
		VALUES ('".$name."', '".$value."')";

		if ($conn->query($sql) === TRUE) {
		    return true;
		} else {
		    return false;
		}
	}
?>