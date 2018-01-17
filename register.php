<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include($_SERVER['DOCUMENT_ROOT']."/template/head.php"); ?>
</head>

<body>
    <?php include($_SERVER['DOCUMENT_ROOT']."/template/anonymous-nav.php"); ?>
    <script>
        $(document).ready(function() {
            initNotification("#main-form");
            $("#main-form").submit(function() {
                $("#btnSubmit").attr("disabled", "true");
                clearNotification();

                $("#notifications").width($("#main-form").width());

                $("#inputName2").val($("#inputName2").val().toUpperCase());

                var name = $("#inputName").val() + " " + $("#inputName2").val(),
                    email = $("#inputEmail").val(),
                    hashPass = getHashAndClean("#inputPassword"),
                    hashPassR = getHashAndClean("#inputPasswordR");

                if (hashPass !== hashPassR) {
                    $("#btnSubmit").removeAttr("disabled");
                    notify("<b>Warning</b> Passwords doesn't match !", "warning");
                    return false;
                }

                ajax_post({
                    url: "/api/user/new",
                    data: {
                        name: name,
                        email: email,
                        password: hashPass
                    },
                    success: function(content) {
                        writeCookie("notify", "success-Your account has been created successfuly !", 1);
                        window.location = "./";
                    },
                    error: function(code, data) {
                        $("#btnSubmit").removeAttr("disabled");
                    }
                });

                return false;
            });
        });

    </script>
    <div class="container">
        <form id="main-form" class="custom-form form-register" method="post">
            <h2 class="form-signin-heading">Register</h2>
            <div class="form-group row">
                <label for="inputName" class="col-sm-4 col-form-label">Name</label>
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="inputName" placeholder="Enter first name" required autocomplete="name"> </div>
                <div class="col-sm-4">
                    <input type="text" class="form-control" id="inputName2" placeholder="Enter last name" required autocomplete="family-name" style="text-transform:uppercase"> </div>
            </div>
            <div class="form-group row">
                <label for="inputEmail" class="col-sm-4 col-form-label">Email address</label>
                <div class="col-sm-8">
                    <input type="email" class="form-control" id="inputEmail" placeholder="Enter email" required autofocus autocomplete="email"> </div>
            </div>
            <div class="form-group row">
                <label for="inputPassword" class="col-sm-4 col-form-label">Password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPassword" placeholder="Enter password" required> </div>
            </div>
            <div class="form-group row">
                <label for="inputPasswordR" class="col-sm-4 col-form-label">Repeat Password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPasswordR" placeholder="Enter same password" required> </div>
            </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-4 offset-sm-4 btn btn-lg btn-primary btn-block" type="submit">Register</button>
            </div>
            <label class="text-center" style="width:100%">Or you already <a href="./">have an account</a> ?</label>
        </form>
    </div>
</body>

</html>
