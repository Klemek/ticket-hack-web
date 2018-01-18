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
        var project_id, user_access, selectedAccess, users = [],
            current_user = <?php echo $_SESSION["user_id"]; ?>;

        function loadInfos() {
            $("#informations").css("display", "none");
            addLoading(".jumbotron");
            ajax_get({
                url: "/api/project/" + project_id,
                success: function(project) {
                    user_access = project.user_access;

                    $("#btnDelete").css("display", user_access >= 4 ? "block" : "none");
                    $("#form-add").css("display", user_access >= 4 ? "block" : "none");
                    $("#new-ticket").css("display", user_access >= 3 ? "block" : "none");

                    $("#new-project").css("display", "block");

                    $("#projectTitle").val(project.name);
                    $("#projectCreationDate").html(prettyDate(project.creation_date));
                    $("#projectCreator").html(project.creator.name);
                    if (project.editor) {
                        $("#projectEdited").css("display", "inline");
                        $("#projectEditionDate").html(prettyDate(project.edition_date));
                        $("#projectEditor").html(project.editor.name);
                    } else {
                        $("#projectEdited").css("display", "none");
                    }
                    $("#userList").html("");
                    users = [];
                    ajax_get({
                        url: "/api/project/" + project_id + "/users",
                        success: function(list) {
                            list.forEach(function(user) {
                                addUser(user.id, user.access_level, user.name, user_access >= 4 && user.id != current_user);
                                users.push(user.id);
                            });
                        }
                    });

                    $("#ticketList").html("");
                    ajax_get({
                        url: "/api/project/" + project_id + "/tickets",
                        success: function(list) {
                            list.forEach(function(ticket) {
                                addTicket(getTicketName(ticket), ticket.name, ticket.type, ticket.priority, ticket.state, ticket.manager ? ticket.manager.name : "");
                            });
                        }
                    });

                    removeLoading();
                    $("#informations").css("display", "block");
                },
                error: function(code, data) {
                    removeLoading();
                }
            });
        }

        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navProjects").addClass("active");

            var simple_id = window.location.href.split("/project/")[1].toUpperCase();
            if (simple_id.indexOf("/") !== -1) {
                writeCookie("notify", "warning-Invalid project id.", 1);
                window.location = "/projects";
                return;
            }

            $("#project-id").html("<b>[" + simple_id + "]</b>");
            $("#projectTitle").val("Loading...");

            ajax_get({
                url: "/api/project/list",
                success: function(content) {
                    content.list.forEach(function(project) {
                        if (project.ticket_prefix == simple_id) {
                            project_id = project.id;
                            user_access = project.access_level;
                        }
                    });

                    if (!project_id) {
                        writeCookie("notify", "warning-Invalid project id.", 1);
                        window.location = "/projects";
                        return;
                    }

                    loadInfos();

                    setInterval(loadInfos, 5 * 60 * 1000);

                    if (user_access >= 4) {
                        registerCustomInput("projectTitle", false, function() {
                            var name = $("#projectTitle").val();
                            ajax_post({
                                url: "/api/project/" + project_id + "/edit",
                                data: {
                                    name: name
                                },
                                success: function(content) {
                                    notify("<b>Success</b> changes saved !", "success");
                                }
                            });
                        });
                    }

                },
            });

            $("#form-add").submit(function() {
                var mail = $("#inputEmail").val();
                ajax_get({
                    url: "/api/user/bymail",
                    data: {
                        mail: mail
                    },
                    success: function(user) {
                        if (users.indexOf(user.id) != -1) {
                            notify("<b>Warning</b> User already associated to project, remove it first.", "warning");
                            $("#inputEmail").val("");
                        } else {
                            ajax_post({
                                url: "/api/project/" + project_id + "/adduser",
                                data: {
                                    user_id: user.id,
                                    access_level: selectedAccess
                                },
                                success: function(user) {
                                    notify("<b>Success</b> User successfuly associated to project.", "success");
                                    $("#inputEmail").val("");
                                    loadInfos();
                                }
                            });
                        }
                    }
                });
                return false;
            });

            initDropdown("dd-access", "access", 1);

            $("#new-ticket").click(function() {
                var win = window.open("/ticket/new", '_blank');
                win.focus();
            });

            $("#btnDelete").click(function() {
                if (confirm('Are you sure you want to delete this project ?')) {
                    ajax_delete({
                        url: "/api/project/" + project_id + "/delete",
                        success: function(content) {
                            writeCookie("notify", "success-The project was successfuly deleted.", 1);
                            window.location = "/projects";
                        }
                    });
                }
                return false;
            });
        });

        function changeDropdown(ddtype, val) {
            switch (ddtype) {
                case "access":
                    selectedAccess = val;
                    break;
            }
        }

        function ticket_click(id) {
            var win = window.open("/ticket/" + id, '_blank');
            win.focus();
        }

        function delete_user(id) {
            ajax_post({
                url: "/api/project/" + project_id + "/removeuser",
                data: {
                    user_id: id
                },
                success: function(user) {
                    notify("<b>Success</b> User successfuly removed from project.", "success");
                    loadInfos();
                }
            });
        }

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-projectTitle" class="form-group row form-custom" style="font-size:2em;">
                <label id="project-id" class="col-form-label" style="margin-left:0.4em;"></label>
                <div class="col-sm-6">
                    <input id="projectTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off">
                </div>
            </form>
            <div id="informations" style="display:none;">

                <h4 style="margin-top:-0.8em;"><small>Created <span id="projectCreationDate" class="text-success"></span> by <span id="projectCreator" class="text-primary"></span><span id="projectEdited"> - Edited <span id="projectEditionDate" class="text-success"></span> by <span id="projectEditor" class="text-primary"></span></span></small></h4>
                <button id="btnDelete" class="btn btn-outline-danger" style="cursor:pointer;display:none;"><i class="fa fa-trash"></i> Delete</button>
                <h3>Associated users</h3>
                <div id="userList" class="row" style="margin:15px;"></div>
                <form id="form-add" class="form-inline" style="display:none;">
                    <input id="inputEmail" class="form-control" type="email" placeholder="Enter user email" required>
                    <div id="dd-access" class="dropdown" style="margin-right:10px;"></div>
                    <button class="btn btn-outline-success" style="cursor:pointer;" type="submit"><i class="fa fa-plus"></i> Add</button>
                </form>
                <h3>Associated tickets</h3>
                <div id="ticketList"></div>
                <div class="ticket new-ticket" id="new-ticket" style="display:none;"> <span title="improvement" class="fa-stack type">
                  <i class="fa fa-square fa-stack-2x"></i>
                  <i class="fa fa-plus fa-stack-1x fa-inverse"></i>
                </span>
                    <h4>Open a new ticket</h4>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
