<?php
/*connects to the database*/
try{
    $db = new PDO("pgsql:user=postgres;dbname=postgres;password=postgres;host=localhost");
}catch(PDOException $e){
    die("Erreur de connexion à la base de donnée");
}
?>