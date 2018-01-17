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
        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navProjects").addClass("active");

            $("#inputPrefix").blur(function() {;
                var val = $("#inputPrefix").val();
                val = val.replace(/[^a-z]/gi, '').toUpperCase();
                $("#inputPrefix").val(val);
            });

            $("#main-form").submit(function() {

                $("#btnSubmit").attr("disabled");

                var title = $("#inputTitle").val(),
                    prefix = $("#inputPrefix").val();

                if (prefix == "NEW") {
                    notify("<b>Warning</b> You cannot create a project with the prefix 'NEW'", "warning");
                    return false;
                }

                ajax_get({
                    url: "/api/project/list",
                    success: function(content) {

                        var cancel = false;

                        content.list.forEach(function(project) {
                            if (project.ticket_prefix == prefix)
                                cancel = true;
                        });

                        if (cancel) {
                            notify("<b>Error</b> You are already associated to a project with the prefix '" + prefix + "'", "danger");
                            return false;
                        }

                        if (!cancel) {
                            ajax_post({
                                url: "/api/project/new",
                                data: {
                                    name: title,
                                    ticket_prefix: prefix
                                },
                                success: function(content) {
                                    writeCookie("notify", "success-Project created successfuly !", 1);
                                    window.location = "/project/" + prefix;
                                },
                                error: function(code, data) {
                                    $("#btnSubmit").removeAttr("disabled");
                                }
                            });
                        }


                    },
                });



                return false;
            });
        });

    </script>
    <div class="container">
        <form id="main-form" class="custom-form form-register" method="post">
            <h2 class="form-signin-heading">Create a new project</h2>
            <div class="form-group row">
                <label for="inputTitle" class="col-sm-4 col-form-label">Project name</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="inputTitle" maxlength="255" placeholder="Enter project title" required autofocus> </div>
            </div>
            <div class="form-group row">
                <label for="inputPrefix" class="col-sm-4 col-form-label">Project prefix</label>
                <div class="col-sm-8">
                    <input type="text" class="form-control" id="inputPrefix" maxlength="4" placeholder="Enter tickets prefix" required style="text-transform:uppercase"> </div>
            </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-4 offset-sm-4 btn btn-lg btn-primary btn-block" type="submit">Create the project</button>
            </div>
        </form>
    </div>
</body>

</html>
