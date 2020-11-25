<?php 
include 'global.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	echo '405-Method Not Allowed';
	exit;
}

$postData				= file_get_contents('php://input');
$xml					= simplexml_load_string($postData);

$openId 				= isset($xml->open_id)		? $xml->open_id 		: 99 ;
$subId 					= isset($xml->sub_id)		? $xml->sub_id  		: 99 ;
$mig_rslt_cd 			= isset($xml->mig_rslt_cd)	? $xml->mig_rslt_cd  	: "" ;
$mig_rsn_cd 			= isset($xml->mig_rsn_cd)	? $xml->mig_rsn_cd  	: "" ;
$mig_rslt 				= isset($xml->mig_rslt)	? $xml->mig_rslt  		: "" ;
$db = new DB();
$sqlMaxIdAuUser 		= "SELECT MAX(au_user_id) as max FROM t_au_user";
$maxAuUserId 			= $db->pdo_query_one($sqlMaxIdAuUser)['max']+1;

if ($mig_rslt != "0"){
	$sqlMaxIdAuUser 	= "SELECT MAX(au_user_id) as max FROM t_au_user_chk";
	$maxAuUserChkId 	= $db->pdo_query_one($sqlMaxIdAuUser)['max']+1;
	$sqlInsertAuUserChk = "	INSERT INTO 
		    					t_au_user_chk 
		    					(	au_user_id,
		    						open_id,
		    						sub_id,
		    						status_flag
		    					)
	    					VALUES 
	    					 	('$maxAuUserChkId','$openId','$subId','99')";

	$db->pdo_execute($sqlInsertAuUserChk);
	return responseError($openId,$subId);
}

$sqlSelectAuUser 		= "SELECT * FROM t_au_user WHERE open_id = '$openId' ";
$auUser     			= $db->pdo_query_one($sqlSelectAuUser);

if (empty($auUser)) {
	$sqlInsertAuUser 	= "
							INSERT INTO 
    							t_au_user (au_user_id, open_id)
    					 	VALUES 
    							('$maxAuUserId', '$openId')";
    $db->pdo_execute($sqlInsertAuUser);


	$sqlUpdateAuUserChk = "
							UPDATE 
								t_au_user_chk
							SET 
								status_flag 	= 2,
								mig_rslt_cd 	= '$mig_rslt_cd',
								mig_rsn_cd 		= '$mig_rsn_cd',
								mig_rslt 		= '$mig_rslt'
							WHERE
								open_id 		= '$openId'
								";
	$db->pdo_execute($sqlUpdateAuUserChk);			

}else {
	$sqlUpdateAuUserChk = "
							UPDATE 
								t_au_user_chk
							SET 
								status_flag 	= 1,
								mig_rslt_cd 	= '$mig_rslt_cd',
								mig_rsn_cd 		= '$mig_rsn_cd',
								mig_rslt 		= '$mig_rslt'
							WHERE
								open_id 		= '$openId'
								
								";

	$db->pdo_execute($sqlUpdateAuUserChk);					
}


return responseSuccess();











