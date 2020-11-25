<?php 

class DB 
{
   	const DB_HOST = "localhost";
   	const DB_NAME = "user_support";
   	const DB_PORT = "5432";
   	const DB_USER = "postgres";
   	const DB_PASSWORD = "";

   	protected $conn;

    public function __construct()
    {
    	$dbhost 	= self::DB_HOST;
	    $dbname 	= self::DB_NAME;
	    $dbport 	= self::DB_PORT;
	    $dbuser 	= self::DB_USER;
	    $dbpassword = self::DB_PASSWORD;
        try {
	    	$this->conn = new PDO("pgsql:host=$dbhost;dbname=$dbname;port=$dbport", $dbuser, $dbpassword);
	    	$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	    } catch (Exception $e) {
	    	  echo "Connection failed: " . $e->getMessage();
	    }
    }

    function pdo_execute($sql){
    	try {
    		$stmt = $this->conn->exec($sql);
    	} catch (Exception $e) {
    		echo $e;
    		exit;
    	}

	}

	function pdo_query($sql){
		try {
			$stmt = $this->conn->prepare($sql);
	        $stmt->execute();
	        $result = $stmt->fetchAll();
		} catch (Exception $e) {
			echo $e;
			exit;
		}
       
        return $result;
	}

	function pdo_query_one($sql){
        
        try {
        	$stmt = $this->conn->prepare($sql);
	        $stmt->execute();
	        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
        	echo $e;
        	exit;
        } 
        return $result;

	  
    }

}

function dd(){
	echo '<pre>';
	var_dump(func_get_args());die;
}

function responseSuccess(){
	$responseXMl = "<?xml version=\"1.0\" encoding=\"Shift_JIS\"?>\n<test_status_check_resp>\n<rslt_cd>00</rslt_cd>\n<rsn_cd>000000</rsn_cd>\n</test_status_check_resp>";

	header("HTTP/1.1 200 OK");
	header("Content-type: text/xml;charset=utf-8");  
	echo $responseXMl;

}

function responseError($openId,$subId){
	$responseXMl = "";
	if ($openId == 99) {

		$responseXMl = "<?xml version=\"1.0\" encoding=\"Shift_JIS\"?><test_mig_check_resp>\n<rslt_cd>10</rslt_cd>\n<rsn_cd>200005</rsn_cd>\n</test_mig_check_resp>";
	}elseif($subId == 99){

		$responseXMl = "<?xml version=\"1.0\" encoding=\"Shift_JIS\"?><test_mig_check_resp>\n<rslt_cd>10</rslt_cd>\n<rsn_cd>200003</rsn_cd>\n</test_mig_check_resp>";
	}
	
	
	header("HTTP/1.1 200 OK");
	header("Content-type: text/xml;charset=utf-8");  
	echo $responseXMl;

}


