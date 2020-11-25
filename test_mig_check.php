<?php 
include 'global.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
	echo '405-Method Not Allowed';
	exit;
}

$postData 				= file_get_contents('php://input');
$xml					= simplexml_load_string($postData);
$openId 				= !empty($xml->open_id)	? $xml->open_id : 99 ;
$subId 					= !empty($xml->sub_id)	? $xml->sub_id  : 99 ;

$db = new DB();


$sqlMaxId 				= "SELECT MAX(au_user_id) FROM t_au_user_chk";
$maxAuUserChkId 		= $db->pdo_query_one($sqlMaxId)['max']+1;

if ($openId == 99 || $subId == 99) {

	
	$sqlInsertAuUserChk = "	INSERT INTO 
		    					t_au_user_chk 
		    					(	au_user_id,
		    						open_id,
		    						sub_id,
		    						status_flag
		    					)
	    					VALUES 
	    					 	('$maxAuUserChkId','$openId','$subId','0')";

	$db->pdo_execute($sqlInsertAuUserChk);

	return responseError($openId,$subId);
}



$sqlInsertAuUserChk 	= "	INSERT INTO 
		    					t_au_user_chk 
		    					(	au_user_id,
		    						open_id,
		    						sub_id,
		    						status_flag
		    					)
	    					VALUES 
	    					 	('$maxAuUserChkId','$openId','subId','0')";

$db->pdo_execute($sqlInsertAuUserChk);



$responseXMl = "<?xml version=\"1.0\" encoding=\"Shift_JIS\"?>\n<test_status_check_resp>\n<rslt_cd>00</rslt_cd>\n<rsn_cd>000000</rsn_cd>\n</test_status_check_resp>";

return responseSuccess();

