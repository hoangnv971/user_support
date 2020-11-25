<?php 

$xml="<?xml version=\"1.0\" encoding=\"Shift_JIS\"?>\n<test_status_check_resp>\n<rslt_cd>00</rslt_cd>\n<rsn_cd>000000</rsn_cd>\n</test_status_check_resp>";
header("HTTP/1.1 200 OK");
header("Content-type: text/xml;charset=utf-8");  
echo $xml;
