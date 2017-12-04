<?php
error_reporting(E_ALL);
//CRONS ARE ONLY FOR THE LIVE SITE
$time_start = microtime(true); //for execution time
$servername = "xxxxxxxx";
$username = "xxxxxxxxxx";
$password = "xxxxxxxxxxx";
$dbname = "xxxxxxxx";
$conn = mysqli_connect($servername, $username, $password, $dbname);
if (!$conn) {
	// mail("cronupdate@imagepanel.net","CRON failed script","Cron script cron_imgdelete.php has failed to connect to the server. \n Reason:\n " . mysqli_connect_error());
	echo "Cron script cron_imgdelete.php has failed to connect to the server. \n Reason:\n " . mysqli_connect_error();
	die();
}

//USE DIFFERENT USER FOR CRON JOBS!
	$sql = "SELECT file_ID, fileTmpName, user_ID FROM file WHERE fileExpireDate < NOW();"; //Select all expired images of non-registered users
	$result = mysqli_query($conn, $sql);
	$dir = "../uploads/";
	$images = array();
	$fileID = array();
	$userID = array();
	$isok = true;
	if (mysqli_num_rows($result) > 0) {
	// Get each image that is expired and assign them to an array
		$index = 0;
	    while($row = mysqli_fetch_assoc($result)) {//assign image's temperary name and their ID in arrays
	       $images[$index] = $row['fileTmpName'];
	       $fileID[$index] = $row['file_ID'];
	       $userID[$index] = $row['user_ID'];
	       $index++;
	    }
	} else {//no files are expired
		// mail("cronupdate@imagepanel.net","CRON imgdelete","Cron script cron_imgdelete.php was completed and no files were expired.");
		echo "Cron script cron_imgdelete.php was completed and no files were expired.";
		die();
	}
	//start the deletion process. Delete actual file from server and delete the database row that's associated with it.
	$message1 = array();
	$message2 = array();
	$index2 = 0;
	$counter = 0;
	$check = true;
	for ($i = 0; $i < count($images); $i++) {
		if ($userID[$i] == 0) {//Normal user's image
			$path = $dir.$images[$i];
			if (unlink($path) == true){//delete file successful 
				$message1[$index2] = "<li>file ".$images[$i]." was deleted from server successfully.</li>\n";
				$check = true;
			}else{ // delete file unsuccessful 
				$message1[$index2] = "<li>file ".$images[$i]." was NOT deleted from server.</li>\n";
				$isok = false;
				$check = false;
			}
		    $sql = "DELETE FROM file WHERE file_ID = ".$fileID[$i].";";
		    if (mysqli_query($conn, $sql)) {//delete DB row successful 
			    $message2[$index2] = "<li>file ".$images[$i]." was deleted from database successfully.</li><br>\n\n";
			    $check2 = true;
			} else {//delete DB row unsuccessful 
			    $message2[$index2] = "\n\n<li>Error deleting record: " . mysqli_error($conn) . "</li><br>";
			    $isok = false;
			    $check2 = false;
			}
			if ($check == true && $check2 == true) {//if checks are not tripped increment counter (used later as a "23 files out of 30 files deleted" indicator)
				$counter++;
			}
			$index2++;
		}else{//registered user's image
			$sql = "SELECT userName FROM login WHERE user_ID =".$userID[$i].";";
			$result = mysqli_query($conn, $sql);
			$row = mysqli_fetch_assoc($result);
			$username = $row["userName"];
			$path = $dir.$username."/".$images[$i];
			if (unlink($path) == true){//delete file successful 
				$message1[$index2] = "<li>file ".$username."/".$images[$i]." was deleted from server successfully.</li>\n";
				$check = true;
			}else{ // delete file unsuccessful 
				$message1[$index2] = "<li>file ".$username."/".$images[$i]." was NOT deleted from server.</li>\n";
				$isok = false;
				$check = false;
			}
		    $sql = "DELETE FROM file WHERE file_ID = ".$fileID[$i].";";
		    if (mysqli_query($conn, $sql)) {//delete DB row successful 
			    $message2[$index2] = "<li>file ".$username."/".$images[$i]." was deleted from database successfully.</li><br>\n\n";
			    $check2 = true;
			} else {//delete DB row unsuccessful 
			    $message2[$index2] = "\n\n<li>Error deleting record: " . mysqli_error($conn) . "</li><br>";
			    $isok = false;
			    $check2 = false;
			}
			if ($check == true && $check2 == true) {//if checks are not tripped increment counter (used later as a "23 files out of 30 files deleted" indicator)
				$counter++;
			}
			$index2++;
		}
	}
	//EMAIL PREP
	// $headers = "MIME-Version: 1.0" . "\r\n";
	// $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
 //    $subject = "CRON imgdelete";
    $t = date("Y-m-d",time());
    $message = "<html><body><h2>This log was made on: ".$t."</h2>\n<ul>";
    foreach (array_combine($message1, $message2) as $message1 => $message2) {
	   $message .= $message1;
	   $message .= $message2;
	}
	if ($isok == true) {
		$message .= "</ul>\n <h3><span style=\"color:green;\">All files successfully removed. (".$counter."/".$index2.")</span></h3>";
	}else{
		$message .= "\n <h3><span style=\"color:red;\">Not all files were removed. (".$counter."/".$index2.")</span></h3>";
	}
	//calculate execution time
	$time_end = microtime(true);
	$time = $time_end - $time_start;
	$time = round($time, 3);
	$message .= "\n<h3>Script executed in " . $time . " seconds.</h3></body></html>";
	 if (mail("xxxxxxxxx",$subject,$message, $headers) == true){
		//echo $message."<br>";
		//echo "sent";
	 }else{
		mail("xxxxxxxxxxxx",$subject,"Failed Boi", $headers);
	}
?>