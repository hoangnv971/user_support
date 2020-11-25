<?php
define('DB_HOST', "localhost");
define('DB_NAME', "user_support");
define('DB_PORT', "5432");
define('DB_USER', "postgres");
define('DB_PASSWORD', "");
define('CSV_FILE', "test_multi-ez_migration_member_list_20201124_000000000264991.csv");
define('FIRST_URL', 'https://st.fep.auone.jp/api/id_link');
define('SECOND_URL', 'https://fep.auone.jp/api/mig_exec');

function pdo_get_connection(){

    $dbhost = DB_HOST;
    $dbname = DB_NAME;
    $dbport = DB_PORT;
    $dbuser = DB_USER;
    $dbpassword = DB_PASSWORD;
    try {
        $pdo = new PDO("pgsql:host=$dbhost;dbname=$dbname;port=$dbport", $dbuser, $dbpassword);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (Exception $e) {
          echo "Connection failed: " . $e->getMessage();
    }
    return $pdo;

}
function pdo_execute($sql){
    $sql_args = array_slice(func_get_args(), 1);
    try{
        $conn = pdo_get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
    }
    catch(PDOException $e){
        throw $e;
    }
    finally{
        unset($conn);
    }
}
function pdo_query($sql){
    $sql_args = array_slice(func_get_args(), 1);
    try{
        $conn = pdo_get_connection();
        $stmt = $conn->prepare($sql);
        $stmt->execute($sql_args);
        $rows = $stmt->fetchAll();
        return $rows;
    }
    catch(PDOException $e){
        throw $e;
    }
    finally{
        unset($conn);
    }
}

function dd(){
    echo '<pre>';
    var_dump(func_get_args());die;
}

function callCURL($url,$inputXml) {

    $curl = curl_init();

    curl_setopt_array($curl, array(
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_ENCODING => "",
    CURLOPT_MAXREDIRS => 10,
    CURLOPT_TIMEOUT => 0,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
    CURLOPT_CUSTOMREQUEST => "POST",
    CURLOPT_POSTFIELDS =>$inputXml,
    CURLOPT_HTTPHEADER => array(
    "Content-Type: text/xml"
    ),
));

    $response = curl_exec($curl);

    curl_close($curl);

    return $response;
}

date_default_timezone_set('Asia/Tokyo');

$fileCSV = fopen(CSV_FILE,"r");
$data =array();
$count = 0;
while (!feof($fileCSV)) {
    $line = fgetcsv($fileCSV, 0, ',');
    $data[$count]['sys_no'] = $line[0];
    $data[$count]['sub_id'] = $line[1];
    $data[$count]['cp_cd'] = $line[2];
    $data[$count]['cp_srv_cd'] = $line[3];
    $data[$count]['item_cd'] = $line[4];
    $data[$count]['member_id'] = $line[5];
    $count ++;
}   
fclose($fileCSV);
unset($data[0]);

foreach ($data as $item) {
    $inputXml= "
        <?xml version=\"1.0\" encoding=\"Shift_JIS\"?>\n<id_link_req>\n    <sys_no>".$item['sys_no']."</sys_no>\n    <sub_id>".$item['sub_id']."</sub_id>\n  ";
    if ($item['sys_no']) {
        $inputXml .="<cp_cd>".$item['cp_cd']."</cp_cd>\n";
    }
    $inputXml .= "<cp_srv_cd>".$item['cp_srv_cd']."</cp_srv_cd>\n <item_cd>".$item['item_cd']."</item_cd>\n    <member_id>".$item['member_id']."</member_id>\n</id_link_req>\n
    ";

    $firstCurl = callCURL(FIRST_URL,$inputXml);
    $firstXml = simplexml_load_string($firstCurl);

    $inputXml=  "<?xml version=\"1.0\" encoding=\"Shift_JIS\"?>
                <mig_exec_req>
                <sys_no>".($firstXml->sys_no ?? "")."</sys_no>
                <sub_id>".($firstXml->sub_id ?? "")."</sub_id>
                <open_id>".($firstXml->open_id ?? "")."</open_id>
                <cp_cd>".($firstXml->cp_cd ?? "")."</cp_cd>
                <cp_srv_cd>".($firstXml->cp_srv_cd ?? "")."</cp_srv_cd>";

    if ($firstXml['sys_no']) {
        $inputXml.="<item_cd>".($firstXml->item_cd ?? "")."</item_cd>";
    }

    $inputXml.= "<member_id>".($item['member_id'] ?? "")."</member_id>".
                "</mig_exec_req>";
    $seccondCurl  = callCURL(SECOND_URL,$inputXml);   
    $seccondXml   = simplexml_load_string($seccondCurl);
    $open_id      = $seccondXml->open_id;
    $sub_id       = $seccondXml->sub_id;

    if ($seccondXml->rslt_cd != "00" || $seccondXml->rsn_cd != "000000") {

        $content  = PHP_EOL.PHP_EOL."[".date("Y-m-d H:i:s")."] :".PHP_EOL."openid: ".($open_id??"null").PHP_EOL."sub_id: ".($sub_id??"null").PHP_EOL.PHP_EOL.'_________________________________________________________';
        $fileLog  = file_put_contents('acount_undefined',$content,FILE_APPEND);
        echo "sub_id :".$item['sub_id']."   error! Check ".__DIR__."/acount_undefined <br/><br/>";
        continue;
    }
    if ($open_id) {
        
        $sqlSelect = "SELECT * FROM t_au_user WHERE open_id = '$open_id'";
        $auUser = pdo_query($sqlSelect);
        $totalUser = count($auUser);
        $sqlInsert = "INSERT INTO 
                            t_au_user_chk
                            (   
                                au_user_id,
                                open_id,
                                continue_account_id,
                                member_manage_no,
                                process_day,
                                process_time,
                                transaction_id,
                                user_enable,
                                entry_date,
                                stop_date,
                                carria_id,
                                device_name,
                                access_from,
                                session_id,
                                status_flag
                            )
                             VALUES";
        if ($totalUser > 1) {
            foreach ($auUser as $user) {
                $sqlInsert .= 
                              "
                              ('".$user['au_user_id']."',".
                              "'".$user['open_id']."',".
                              "'".$user['continue_account_id']."',".
                              "'".$user['member_manage_no']."',".
                              "'".$user['process_day']."',".
                              "'".$user['process_time']."',".
                              "'".$user['transaction_id']."',".
                              "'".$user['user_enable']."',".
                              "'".$user['entry_date']."',".
                              "'".$user['stop_date']."',".
                              "'".$user['carria_id']."',".
                              "'".$user['device_name']."',".    
                              "'".$user['access_from']."',".    
                              "'".$user['session_id']."',"
                              ."'2'),";
            }
            $sqlInsert = substr($sqlInsert,0,-1).';';

            pdo_execute($sqlInsert);
            echo "success more than 1 account! <br/><br/>";
        }elseif ($totalUser == 1) {
            extract($auUser[0]);
            $sqlInsert .= "
                          ('".$auUser[0]['au_user_id']."',".
                          "'".$auUser[0]['open_id']."',".
                          "'".$auUser[0]['continue_account_id']."',".
                          "'".$auUser[0]['member_manage_no']."',".
                          "'".$auUser[0]['process_day']."',".
                          "'".$auUser[0]['process_time']."',".
                          "'".$auUser[0]['transaction_id']."',".
                          "'".$auUser[0]['user_enable']."',".
                             ($auUser[0]['entry_date'] ? "'".$auUser[0]['entry_date']."'": "NULL").",".
                             ($auUser[0]['stop_date'] ? "'".$auUser[0]['entry_date']."'": "NULL").",".
                          "'".$auUser[0]['carria_id']."',".
                          "'".$auUser[0]['device_name']."',".   
                          "'".$auUser[0]['access_from']."',".   
                          "'".$auUser[0]['session_id']."',"
                          ."'1');"; 
              pdo_execute($sqlInsert);
              echo "success 1 account!<br/><br/>";

        }else{
            $content = PHP_EOL."[".date("Y-m-d H:i:s")."] : openid not found! ".PHP_EOL."openid: ".$open_id.PHP_EOL."sub_id: ".$sub_id.PHP_EOL.PHP_EOL.'_________________________________________________________';
            $fileLog = file_put_contents('acount_undefined',$content,FILE_APPEND);
            echo 
        }
    }
}









