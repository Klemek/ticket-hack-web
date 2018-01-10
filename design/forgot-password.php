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
                        $("#inputEmail").val("");
                        $("#notifications").html('<div class="alert alert-success alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong>Success</strong> We sent you an email with your new password.</div>')
                    }
                }, 500);
                return false;
            });
        });

    </script>
    <div class="container">
        <form id="main-form" class="custom-form" method="post">
            <div id="notifications"></div>
            <h2 class="form-signin-heading">Forgot password ?</h2>
            <div class="form-group">
                <label for="inputEmail">Type your email address</label>
                <input type="email" class="form-control" id="inputEmail" placeholder="Enter email"> </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-6 offset-sm-3 btn btn-lg btn-primary btn-block" type="submit">Reset my password</button>
            </div>
        </form>
    </div>
</body>

</html>
