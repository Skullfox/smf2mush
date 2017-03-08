<?php
header('Content-Type: application/json');
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * smf2Mush class
 *
 * A class for http://mushraider.com/ to support a SMF forum login.
 * Place the class somewhere on you webserver, dont forget to change the settigns, and point in the bridge
 * settings to /smf2mush.php
 *
 * @author     Skullfox
 * @link       https://github.com/Skullfox/smf2mush
 */

class smf2Mush{

	/* Change me */
	private $salt = "xxx"; // Your mushraider secret key, NEVER SHARE THIS KEY
	private $smfUserTable = "smf_members"; //smf member table

	private $database = "smf"; // database which smf is installed
	private $dbHost = "localhost";
	private $dbUser = "root";
	private $dbPw = "root";

	private $adminGroups = array(); //add your smf admin group id here for mushraider admin permissions
	private $officerGroups = array(); //same like above only with mushraider officer permissions

	private $disableLog = false; // Disable error report, not recommend
	/* Do not touch below */
  public $role = "member";
  public $dbc = null;

  public $mushUser = null;
  public $mushPw = null;
  public $mushPwDecr = null;

  public $smfUserData = null;

  public $status = false;

  public function __construct( $post ) {

		$this->setup($post);
		$this->connectDB();
		$this->getUser();
		$this->decryptMush();
		$this->checkPassword();
		$this->sendAnswer();

  }

	public function setup($post){

		if(empty($post['login']) || empty($post['pwd'])) {
	    $this->sendAbort("no login or password provided");
		}else{
			$this->mushUser = $post["login"];
			$this->mushPw = $post["pwd"];
		}
	}

  public function connectDB(){

    try {
  		$db = new mysqli($this->dbHost, $this->dbUser, $this->dbPw, $this->database);

			if ($db->connect_errno) {
			   	$this->reportError( $db->connect_error );
			}else{
					$this->dbc = $db;
			};

    } catch (Exception $e) {
      $this->reportError($e);
    }
  }

	public function assignPermissions(){

		if( in_array( $this->smfUserData["group"], $this->adminGroups) ){
			$this->role = "admin";
		}elseif( in_array( $this->smfUserData["group"], $this->officerGroups) ){
			$this->role = "officer";
		}else{
			$this->role = "member";
		};

	}

  public function reportError($error,$slug = "error"){

			if(!$this->disableLog){
				$msg = "[smf2Mush][" . date('Y-m-d h:i:s') . "] ";
				$msg.= ( is_array($error) ? implode(" ",$error) : $error );
				error_log($msg);
			};
  }

	public function getUser(){

    $db = $this->dbc;
    try {

				$reqUser = $db->real_escape_string($this->mushUser);

				$query = "SELECT * FROM " . $this->smfUserTable . " WHERE member_name = '" . $reqUser . "'";

				$result = $db->query($query);

				$user = $result->fetch_assoc();

    } catch (Exception $e) {
      $this->reportError($e);
    }
    if($user == NULL){
				$this->reportError("User '" . $this->mushUser . "' didnt exist in the database");
        $this->sendAbort("user didnt exist");
    }else{

      $data = array(
        "user" => $user["member_name"],
        "password" => $user["passwd"],
        "email" => $user["email_address"],
        "group" => $user["id_group"]
      );

      $this->smfUserData = $data;

    }
  }

  public function checkPassword(){

		$mushUser = $this->mushUser;
		$mushPw = $this->mushPw;

		$hash = sha1(strtolower( $this->mushUser) . trim($this->mushPwDecr) );

    if( $hash == $this->smfUserData["password"]){
      $this->status = true;
			$this->assignPermissions();
    }else{
      $this->status = false;
    }
  }

	public function decryptMush(){

		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
		$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
		$pwd = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $this->salt, stripslashes( $this->mushPw ), MCRYPT_MODE_ECB, $iv);

		$this->mushPwDecr = $pwd;

	}

  public function sendAnswer(){

    $userInfos = array();
    $userInfos['authenticated'] = $this->status;
    $userInfos['email'] = $this->smfUserData["email"];
    $userInfos['role'] = $this->role;
    echo json_encode($userInfos);

  }

  public function sendAbort($msg = "an unknown error has occurred"){

    echo json_encode(array('authenticated' => false,"msg" => $msg));
    exit;

  }

}

new smf2Mush( $_POST );

?>
