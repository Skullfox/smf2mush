# smf2mush
A class for http://mushraider.com/ to support a SMF forum login

Place the smf2mush.php on your webserver and change the config stuff on top.

	private $salt = "xxx"; // Your mushraider secret key, NEVER SHARE THIS KEY
	private $smfUserTable = "smf_members"; //smf member table

	private $database = "smf"; // database which smf is installed
	private $dbHost = "localhost";
	private $dbUser = "root";
	private $dbPw = "root";

In the MushRaider bridge settigns point now to this file http://myawsomeguild.yolo/smf/smf2mush.php
