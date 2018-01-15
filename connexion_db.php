<?php
/*connects to the database*/
try{
    $db = new PDO("pgsql:user=php;dbname=postgres;password=password;host=localhost");
}catch(PDOException $e){
    die("Erreur de connexion à la base de donnée");
    http_response_code(500);
    $output = array(
        "status"=>500,
        "result"=>"error",
        "error"=>"Error connecting to database"
    );
    echo json_encode($output);
    exit;
}

/** prepare and execute the query
* $req = request (string)
* $values = array
@return PDOStatement $sth
**/
function execute($req, $values){
    global $db;
    $sth = $db->prepare($req);

    if (! $sth){
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

/*apply the init SQL script*/
function init_database(){
    global $db;
    $path_to_init = "./sql/initdb.sql";

    $file = file_get_contents($path_to_init);

    $db->exec($file);
}

?>
