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
                        window.location = "./tickets";
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
        <form id="main-form" class="custom-form form-signin" method="post">
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
