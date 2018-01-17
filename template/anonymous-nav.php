<?php
/*start the session*/
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION["user_id"])){
    header("location:/tickets");
}
?>

    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <a class="navbar-brand" href="/"><i class="fa fa-ticket"></i> Ticket'Hack</a> </nav>
    <div class="container">
