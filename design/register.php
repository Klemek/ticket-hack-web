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

                var url = "http://echo.jsontest.com/result/ok";
                if (randInt(0, 1) == 0)
                    url = "http://echo.jsontest.com/result/error/message/error";

                $.ajax({
                    url: url, //"./api/user/new"
                    method: 'POST',
                    data: {
                        name: name,
                        email: email,
                        pass: hashPass
                    },
                    success: function(result) {
                        if (typeof result !== "object") { //mime type: text
                            if (!validJSON(result + "")) {
                                console.log("invalid json : " + result);
                                notify("<b>Error</b> internal error", "danger");
                                return;
                            }
                            var result = $.parseJSON(result);
                        }

                        if (result.result == "ok") {
                            writeCookie("notify", "success-Your account has been created successfuly !", 1)
                            window.location = "./";
                        } else {
                            notify("<strong>Error</strong> " + result.message, "danger");
                        }

                        $("#btnSubmit").removeAttr("disabled");
                    },
                    error: function(result, data) {
                        if (result.status == 0 || result.status == 404) {
                            notify("<b>Error</b> internal error", "danger");
                            console.log("unreachable url : " + url);
                        } else {
                            notify("<b>Error</b> internal error", "danger");
                            console.log("server error " + result.status);
                        }
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
                    <input type="text" class="form-control" id="inputName2" placeholder="Enter last name" required autocomplete="family-name"> </div>
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
