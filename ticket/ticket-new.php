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
            $("#navTickets").addClass("active");
            autoTextArea("inputDesc");

            $("#btnSubmit").attr("disabled");

            ajax_get({
                url: "/api/project/list",
                success: function(content) {
                    projects = {};
                    first_id = null;
                    content.list.forEach(function(project) {
                        if (project.access_level >= 3) {
                            projects[project.id] = project.ticket_prefix;
                            if (first_id == null)
                                first_id = project.id;
                        }
                    });

                    if (Object.keys(projects).length === 0) {
                        writeCookie("notify", "warning-No projects allow you to create tickets.", 1);
                        window.location = "/tickets";
                        return;
                    } else {
                        initDropdown("dd-project", "project", first_id, projects);
                        $("#btnSubmit").removeAttr("disabled");
                    }
                }
            });

            initDropdown("dd-status", "status", 0);
            initDropdown("dd-type", "type", 0);
            initDropdown("dd-priority", "priority", 0);

            $("#main-form").submit(function() {
                $("#btnSubmit").attr("disabled");
                return false;
            });
        });

        function changeStatus(status) {

        }

        function changeType(type) {

        }

        function changePriority(priority) {

        }

    </script>
    <div class="container">
        <form id="main-form" class="custom-form form-register" method="post">
            <h2 class="form-signin-heading">Create a new ticket</h2>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Project</label>
                <div class="col-sm-10">
                    <div class="dropdown" id="dd-project"></div>
                </div>
            </div>
            <div class="form-group row">
                <label for="inputTitle" class="col-sm-2 col-form-label">Title</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="inputTitle" maxlength="256" placeholder="Enter ticket title" autofocus required> </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Status</label>
                <div class="col-sm-10">
                    <div class="dropdown" id="dd-status"></div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Manager</label>
                <div class="col-sm-10">
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Type</label>
                <div class="col-sm-10">
                    <div class="dropdown" id="dd-type"></div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Priority</label>
                <div class="col-sm-10">
                    <div class="dropdown" id="dd-priority"></div>
                </div>
            </div>
            <div class="form-group row">
                <label for="inputDesc" class="col-sm-2 col-form-label">Description</label>
                <div class="col-sm-10">
                    <textarea type="text" class="form-control" id="inputDesc" maxlength="4096" autocomplete="off" wrap="hard" style="resize: none;" placeholder="Enter ticket description"></textarea> </div>
            </div>
            <div class="row">
                <button id="btnSubmit" class="col-sm-4 offset-sm-4 btn btn-lg btn-primary btn-block" type="submit">Create the ticket</button>
            </div>
        </form>
    </div>
</body>

</html>
