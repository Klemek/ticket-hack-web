<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include("./template/head.php") ?>
</head>

<body>
    <?php include("./template/anonymous-nav.php") ?>
    <div class="container">
        <div class="jumbotron">
            <img class="img-fluid" style="max-width:50%;margin-left:25%;" src="/img/error-min.jpg">
            <h5 class="text-center">
                <?php
                    if(isset($_GET["error"])){
                        switch($_GET["error"]){
                            case 401:
                                echo "<b>Unauthorized (401)</b><br/>A chipmunk ID card is required to access this area.<br/>(You must log in)";
                                break;
                            case 403:
                                echo "<b>Forbidden (403)</b><br/>Your chipmunk ID card does not allow you to enter this area.";
                                break;
                            case 404:
                                echo "<b>Not Found (404)</b><br/>The chipmunk you are looking for isn't here.";
                                break;
                            default:
                                echo "<b>Error ".$_GET["error"]."</b><br/>Something went wrong.<br/>We're sorry for the inconvenience.";
                                break;
                        }
                    }else{
                        header('Location: /');  
                    }
                ?>
            </h5>
        </div>
    </div>
</body>

</html>
