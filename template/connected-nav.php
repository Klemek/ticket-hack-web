<?php
/*start the session*/
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user_id"])){
    header("location:/");
}
?>

    <nav class="navbar navbar-expand-md navbar-dark fixed-top bg-dark">
        <a class="navbar-brand" href="./"><i class="fa fa-ticket"></i> Ticket'Hack</a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarsMain" aria-controls="navbarsMain" aria-expanded="false" aria-label="Toggle navigation"> <span class="navbar-toggler-icon"></span> </button>
        <div class="collapse navbar-collapse" id="navbarsMain">
            <ul class="navbar-nav mr-auto">
                <li id="navTickets" class="nav-item"><a class="nav-link" href="./tickets">Tickets</a></li>
                <li id="navProjects" class="nav-item"><a class="nav-link" href="./projects">Projects</a></li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownUser" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <?php echo $_SESSION["user"]["name"]; ?>
                    </a>
                    <div class="dropdown-menu" aria-labelledby="dropdownUser">
                        <a onclick="logout()" class="dropdown-item" href="#">Logout</a>
                        <a class="dropdown-item" href="./change-password">Change password</a>
                        <a class="dropdown-item" target="_blank" href="/phppgadmin">Database</a>
                    </div>
                </li>
            </ul>
            <!--<form id="form-search" class="form-inline my-2 my-lg-0">
                <input class="form-control mr-sm-2" type="text" placeholder="Search" aria-label="Search">
                <button class="btn btn-outline-success my-2 my-sm-0" type="submit"><i class="fa fa-search"></i></button>
            </form>-->
        </div>
    </nav>
    <script>
        function logout() {
            ajax_get({
                url: "/api/user/disconnect",
                success: function(content) {
                    window.location = "./";
                }
            });
        }

    </script>
