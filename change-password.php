<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include($_SERVER['DOCUMENT_ROOT']."/template/head.php"); ?>
</head>

<body>
    <?php include($_SERVER['DOCUMENT_ROOT']."/template/connected-nav.php"); ?>
    <script>
        var currEmail = '<?php echo $_SESSION["user"]["email"]; ?>';

        //Start page treatment
        $(document).ready(function() {
            initNotification("#main-form");
            $("#dropdownUser").addClass("active");

            //changing password
            $("#main-form").submit(function() {

                $("#btnSubmit").attr("disabled", "true");
                $("#notifications").html("");

                var hashOldPass = getHashAndClean("#inputOldPass"),
                    hashPass = getHashAndClean("#inputPass1"),
                    hashPassR = getHashAndClean("#inputPass2");

                if (hashPass !== hashPassR) {
                    $("#btnSubmit").removeAttr("disabled");
                    notify("<b>Warning</b> Passwords doesn't match !", "warning");
                    return false;
                }

                ajax_post({
                    url: "/api/user/connect",
                    data: {
                        email: currEmail,
                        password: hashOldPass
                    },
                    success: function(content) {
                        ajax_post({
                            url: "/api/user/me/edit",
                            data: {
                                password: hashPass
                            },
                            success: function(content) {
                                writeCookie("notify", "success-Password changed successfully !", 1);
                                window.location = "./";
                            },
                            error: function(code, data) {
                                $("#btnSubmit").removeAttr("disabled");
                            }
                        });
                    },
                    error: function(code, data) {
                        $("#btnSubmit").removeAttr("disabled");
                        clearNotification();
                        notify("<b>Error</b> wrong current password !", "danger");
                    }
                });

                return false;
            });
        });

    </script>
    <div class="container">
        <form id="main-form" class="custom-form form-register" method="post">
            <div id="notifications"></div>
            <h2 class="form-signin-heading">Change password</h2>
            <div class="form-group row">
                <label for="inputOldPass" class="col-sm-4 col-form-label">Current password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputOldPass" placeholder="Enter your current password" required> </div>
            </div>
            <div class="form-group row">
                <label for="inputPass1" class="col-sm-4 col-form-label">New password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPass1" placeholder="Enter the new password" required> </div>
            </div>
            <div class="form-group row">
                <label for="inputPass2" class="col-sm-4 col-form-label">Repeat new password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPass2" placeholder="Enter the new password again" required> </div>
            </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-6 offset-sm-3 btn btn-lg btn-primary btn-block" type="submit">Change my password</button>
            </div>
        </form>
    </div>
</body>

</html>
