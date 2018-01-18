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
        var projects = {},
            managers = {},
            selectedStatus, selectedType, selectedPriority, selectedProject, selectedManager;

        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navTickets").addClass("active");
            autoTextArea("inputDesc");

            ajax_get({
                url: "/api/project/list",
                success: function(content) {
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
                        for (project_id in projects) {
                            loadManagers(project_id);
                        }
                    }
                }
            });

            initDropdown("dd-status", "status", 0);
            initDropdown("dd-type", "type", 0);
            initDropdown("dd-priority", "priority", 0);

            $('#datetimepicker').datepicker({
                format: "dd/mm/yyyy",
                maxViewMode: 2,
                todayHighlight: true,
                orientation: "bottom auto",
                clearBtn: true,
                autoclose: true
            });

            $("#main-form").submit(function() {
                if (Object.keys(projects).length > 0 && Object.keys(managers).length > 0) {
                    $("#btnSubmit").attr("disabled");

                    var name = $("#inputTitle").val(),
                        desc = $("#inputDesc").val(),
                        date = $('#datetimepicker').datepicker('getDate');

                    var data = {
                        name: name,
                        priority: selectedPriority,
                        description: desc,
                        type: selectedType,
                        state: selectedStatus
                    };

                    if (selectedManager > 0)
                        data.manager = selectedManager;

                    if (date != null)
                        data.due_date = date.toJSON();

                    ajax_post({
                        url: "/api/project/" + selectedProject + "/addticket",
                        data: data,
                        success: function(ticket) {
                            writeCookie("notify", "success-Ticket created successfuly !", 1);
                            window.location = "/ticket/" + getTicketName(ticket);
                        },
                        error: function() {
                            $("#btnSubmit").removeAttr("disabled");
                        }
                    });
                }
                return false;
            });
        });

        function loadManagers(project_id) {
            managers[project_id] = {
                0: "Nobody"
            };
            ajax_get({
                url: "/api/project/" + project_id + "/users",
                success: function(list) {
                    list.forEach(function(user) {
                        managers[project_id][user.id] = user.name;
                    });
                    if (Object.keys(managers).length == Object.keys(projects).length) {
                        initDropdown("dd-manager", "manager", 0, managers[first_id]);
                        $("#btnSubmit").removeAttr("disabled");
                    }
                }
            });
        }

        function changeDropdown(ddtype, val) {
            switch (ddtype) {
                case "status":
                    selectedStatus = val;
                    break;
                case "type":
                    selectedType = val;
                    break;
                case "priority":
                    selectedPriority = val;
                    break;
                case "project":
                    if (Object.keys(managers).length > 0) {
                        initDropdown("dd-manager", "manager", 0, managers[val]);
                    }
                    selectedProject = val;
                    break;
                case "manager":
                    selectedManager = val;
                    break;
            }
        }

    </script>
    <div class="container">
        <form id="main-form" class="custom-form form-register" method="post">
            <h2 class="form-signin-heading">Create a new ticket</h2>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Project</label>
                <div class="col-sm-10">
                    <div class="dropdown" id="dd-project">
                        <div><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><span class="sr-only">Loading...</span></div>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <label for="inputTitle" class="col-sm-2 col-form-label">Title</label>
                <div class="col-sm-10">
                    <input type="text" class="form-control" id="inputTitle" maxlength="256" placeholder="Enter ticket title" autofocus required> </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Status</label>
                <div class="col-sm-3">
                    <div class="dropdown" id="dd-status"></div>
                </div>
                <label class="offset-sm-1 col-sm-2 col-form-label">Manager</label>
                <div class="col-sm-3" id="dd-manager">
                    <div><i class="fa fa-spinner fa-spin fa-2x fa-fw"></i><span class="sr-only">Loading...</span></div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Type</label>
                <div class="col-sm-3">
                    <div class="dropdown" id="dd-type"></div>
                </div>
                <label class="offset-sm-1 col-sm-2 col-form-label">Priority</label>
                <div class="col-sm-3">
                    <div class="dropdown" id="dd-priority"></div>
                </div>
            </div>
            <div class="form-group row">

            </div>
            <div class="form-group row">
                <label class="col-sm-2 col-form-label">Due date</label>
                <div class="col-sm-10">
                    <div class="input-group date" data-date-format="mm/dd/yyyy" data-provide="datepicker" id="datetimepicker">
                        <input type="text" readonly class="form-control" placeholder="No date">
                        <div class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </div>
                    </div>
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
