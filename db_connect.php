<?php
/*connects to the database*/
try{
    $db = new PDO("pgsql:user=php;dbname=postgres;password=password;host=localhost");
}catch(PDOException $e){
    http_response_code(500);
    $output = array(
        "status"=>500,
        "result"=>"error",
        "error"=>"Error connecting to database"
    );
    echo json_encode($output);
    exit;
}

/** 
* prepare and execute the query. shortcut for $db->prepare($req)->execute($values); 
* $req = request (string)
* $values = array : can be unindexed or indexed. 
*
* note all null values will be replaced by a NULL string
* @return PDOStatement $sth
**/
function execute($req, $values){
    global $db;
    $sth = $db->prepare($req);
    
    foreach($values as $key=>$v){
        if ($v === null){
            $values[$key] = "NULL";
        }
    }

    if (! $sth){//shouldn't happen - but sometimes, it happen. 
        http_response_code(500);
        $output = array(
            "status"=>500,
            "result"=>"error",
            "error"=>"SQL Error : ".$db->errorInfo()
        );
        echo json_encode($output);
        exit;
    }

    $sth->execute($values);

    return $sth;
}
//php file : do not put "? >" at the end to the risk of having a whitespace included 