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

            var notf = readCookie("notify");
            if (notf && notf.length > 0) {
                notify(notf);
                eraseCookie("notify");
            }

            $("#main-form").submit(function() {
                $("#btnSubmit").attr("disabled", "true");
                clearNotification();

                var hashPass = CryptoJS.SHA256($("#inputPassword").val()).toString();
                $("#inputPassword").val("");

                var url = "http://echo.jsontest.com/result/ok";
                if (randInt(0, 1) == 0)
                    url = "http://echo.jsontest.com/result/error/message/error";

                $.ajax({
                    url: url,
                    method: 'GET',
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
                            window.location = "./tickets";
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
        <form id="main-form" class="custom-form form-signin">
            <div id="notifications"></div>
            <h2 class="form-signin-heading">Please sign in</h2>
            <label for="inputEmail" class="sr-only">Email address</label>
            <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
            <label for="inputPassword" class="sr-only">Password</label>
            <input type="password" id="inputPassword" class="form-control" placeholder="Password" required>
            <div class="checkbox">
                <label>
                    <input type="checkbox" value="remember-me"> Remember me </label>
            </div>
            <button id="btnSubmit" class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
            <label class="text-center" style="width:100%">Or create a <a href="./register">new account</a>
                <br/><small><a href="./forgot-password">Forgot your password ?</a></small></label>
        </form>
    </div>
</body>

</html>
