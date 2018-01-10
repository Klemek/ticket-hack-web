<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include("../template/head.php") ?>
</head>

<body>
    <?php include("../template/anonymous-nav.php") ?>
    <script>
        $(document).ready(function() {
            $("#main-form").submit(function() {
                $("#btnSubmit").attr("disabled", "true");
                $("#notifications").html("");
                //fake ajax
                setTimeout(function() {
                    if ($("#inputPassword").val() == "test") {
                        window.location = "template-ticket-list.html";
                    } else {
                        $("#btnSubmit").removeAttr("disabled");
                        $("#inputPassword").val("");
                        $("#notifications").html('<div class="alert alert-danger alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong>Error</strong> Invalid email or password.</div>')
                    }
                }, 500);
                return false;
            });
        });

    </script>
    <div class="container">
        <form id="main-form" class="custom-form form-register" method="post">
            <div id="notifications"></div>
            <h2 class="form-signin-heading">Register</h2>
            <div class="form-group row">
                <label for="inputEmail" class="col-sm-4 col-form-label">Email address</label>
                <div class="col-sm-8">
                    <input type="email" class="form-control" id="inputEmail" placeholder="Enter email"> </div>
            </div>
            <div class="form-group row">
                <label for="inputName" class="col-sm-4 col-form-label">Username</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="inputName" placeholder="Enter username"> </div>
            </div>
            <div class="form-group row">
                <label for="inputPassword" class="col-sm-4 col-form-label">Password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPassword" placeholder="Enter password"> </div>
            </div>
            <div class="form-group row">
                <label for="inputPasswordR" class="col-sm-4 col-form-label">Repeat Password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPasswordR" placeholder="Enter same password"> </div>
            </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-4 offset-sm-4 btn btn-lg btn-primary btn-block" type="submit">Register</button>
            </div>
            <label class="text-center" style="width:100%">Or you already <a href="./">have an account</a> ?</label>
        </form>
    </div>
</body>

</html>
