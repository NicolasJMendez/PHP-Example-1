<?php
//-----------------This file is used for global variables-------------
//Set to 0 if you're using a staging server
//Set to 1 before going live
define("isLive", 1);
//MAX UPLOAD IN BYTES: (10mbyes = 10485760)
define("maxUploadSize", 10485760);
	if (isLive == 0){//local
		define("root_Dir", "localhost/");
		//DB--v-----------------------------------------
			define('dbhost', "localhost");
			define('dbuser', "localroot");
			define('dbpassword', "xxxxxxxxxxxxxx");
			define('db', "xxxxxxxx");
		//DB--^-----------------------------------------
	}else if (isLive == 1){//live
		define("root_Dir", "http://www.imagepanel.net/");
		//DB--v-----------------------------------------
			define('dbhost', "localhost");
			define('dbuser', "imagepanel_user");
			define('dbpassword', "xxxxxxxxxx");
			define('db', "xxxxxxx");
		//DB--^-----------------------------------------
	}
//-----------------This file is used for global variables-------------
?>