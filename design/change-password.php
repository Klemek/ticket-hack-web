<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include("../template/head.php") ?>
</head>

<body>
    <?php include("../template/connected-nav.php") ?>
    <script>
        $(document).ready(function() {
            $("#dropdownUser").addClass("active");
            $("#dropdownUser").html(randString(fakeUserNames));

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
                        $("#notifications").html('<div class="alert alert-success alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><strong>Success</strong> Password changed successfully.</div>')
                    }
                }, 500);
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
                    <input type="password" class="form-control" id="inputOldPass" placeholder="Enter your current password"> </div>
            </div>
            <div class="form-group row">
                <label for="inputPass1" class="col-sm-4 col-form-label">New password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPass1" placeholder="Enter the new password"> </div>
            </div>
            <div class="form-group row">
                <label for="inputPass2" class="col-sm-4 col-form-label">Repeat new password</label>
                <div class="col-sm-8">
                    <input type="password" class="form-control" id="inputPass2" placeholder="Enter the new password again"> </div>
            </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-6 offset-sm-3 btn btn-lg btn-primary btn-block" type="submit">Change my password</button>
            </div>
        </form>
    </div>
</body>

</html>
