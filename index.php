<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include("./template/head.php") ?>
</head>

<body>
    <?php include("./template/anonymous-nav.php") ?>
    <script>
        $(document).ready(function() {
            initNotification("#main-form");

            var notf = readAndErase("notify");
            if (notf && notf.length > 0)
                notify(notf);


            $("#main-form").submit(function() {
                $("#btnSubmit").attr("disabled", "true");
                clearNotification();

                var email = $("#inputEmail").val(),
                    hashPass = getHashAndClean("#inputPassword");

                ajax_post({
                    url: "/api/user/connect",
                    data: {
                        email: email,
                        password: hashPass
                    },
                    success: function(content) {
                        window.location = "./tickets";
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
        <form id="main-form" class="custom-form form-signin">
            <img class="img-fluid d-none d-sm-block" src="/img/ticket-hack-min.jpg">
            <h2 class="form-signin-heading">Please sign in</h2>
            <label for="inputEmail" class="sr-only">Email address</label>
            <input type="email" id="inputEmail" class="form-control" placeholder="Email address" required autofocus>
            <label for="inputPassword" class="sr-only">Password</label>
            <input type="password" id="inputPassword" class="form-control" placeholder="Password" required>
            <button id="btnSubmit" class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
            <label class="text-center" style="width:100%">Or create a <a href="./register">new account</a></label>
        </form>
    </div>
</body>

</html>
