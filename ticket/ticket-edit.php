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
        var project_id, simple_id, user_access, ticket_id, cancel_save, managers;

        //Start page treatment
        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navTickets").addClass("active");

            //check ticket name validity
            var id = window.location.href.split("/ticket/")[1].toUpperCase().replace("#", "");
            if (id.indexOf("/") !== -1 || id.split("-").length !== 2) {
                writeCookie("notify", "warning-Invalid ticket id. / or -", 1);
                window.location = "/tickets";
                return;
            }

            $("#ticket-id").html("<b>[" + id + "]</b>");
            $("#ticketTitle").val("Loading...");

            var ticket_prefix = id.split("-")[0];
            simple_id = id.split("-")[1];

            if (!isNumeric(simple_id)) {
                writeCookie("notify", "warning-Invalid ticket id.", 1);
                window.location = "/tickets";
                return;
            }

            simple_id = parseInt(simple_id);

            //init datetime picker
            $('#datetimepicker').datepicker({
                format: "dd/mm/yyyy",
                maxViewMode: 2,
                todayHighlight: true,
                orientation: "bottom auto",
                clearBtn: true,
                autoclose: true
            });

            $("#datetimepicker").datepicker()
                .on("changeDate", function(e) {

                    var date = $('#datetimepicker').datepicker('getDate');
                    saveInfo({
                        due_date: date ? getGMTDate(date).toJSON() : null
                    });
                });

            //check project name from list
            ajax_get({
                url: "/api/project/list",
                success: function(content) {
                    content.list.forEach(function(project) {
                        if (project.ticket_prefix == ticket_prefix) {
                            project_id = project.id;
                            user_access = project.access_level;
                        }
                    });

                    if (!project_id) {
                        writeCookie("notify", "warning-Invalid ticket id. project", 1);
                        window.location = "/tickets";
                        return;
                    }

                    loadInfos();

                    //refresh every 5 minutes
                    setInterval(loadInfos, 5 * 60 * 1000);

                    //user can edit ticket
                    if (user_access >= 3) {
                        registerCustomInput("ticketTitle", false, function() {
                            saveInfo({
                                name: $("#ticketTitle").val()
                            });
                        });
                        registerCustomInput("ticketDesc", true, function() {
                            saveInfo({
                                description: $("#ticketDesc").val()
                            });
                        }, autoscroll = false);
                        initDropdown("dd-status", "status", 0);
                        initDropdown("dd-type", "type", 0);
                        initDropdown("dd-priority", "priority", 0);
                        $("#btnDelete").css("display", "block");
                    } else {
                        initDropdown("dd-status", "status", 0, {}, true);
                        initDropdown("dd-type", "type", 0, {}, true);
                        initDropdown("dd-priority", "priority", 0, {}, true);
                    }
                },
            });

            //deleting the ticket
            $("#btnDelete").click(function() {
                if (confirm('Are you sure you want to delete this ticket ?')) {
                    ajax_delete({
                        url: "/api/ticket/" + ticket_id + "/delete",
                        success: function(content) {
                            writeCookie("notify", "success-The ticket was successfuly deleted.", 1);
                            window.location = "/tickets";
                        }
                    });
                }
                return false;
            });
        });

        //load all of ticket's informations
        function loadInfos() {
            $("#informations").css("display", "none");
            addLoading(".jumbotron");
            //load all possible managers for project
            managers = {
                0: "Nobody"
            };
            ajax_get({
                url: "/api/project/" + project_id + "/users",
                success: function(content) {
                    content.list.forEach(function(user) {
                        managers[user.id] = user.name;
                    });
                    initDropdown("dd-manager", "manager", 0, managers, user_access < 3);
                    //get ticket informations
                    ajax_get({
                        url: ticket_id ? "/api/ticket/" + ticket_id : "/api/project/" + project_id + "/ticket/" + simple_id,
                        success: function(ticket) {
                            ticket_id = ticket.id;
                            cancel_save = true;

                            updateDropdown("status", ticket.state);
                            updateDropdown("priority", ticket.priority);
                            updateDropdown("type", ticket.type);

                            if (ticket.manager) {
                                if (!managers[ticket.manager_id]) {
                                    managers[ticket.manager_id] = ticket.manager.name;
                                    initDropdown("dd-manager", "manager", 0, managers, user_access < 3);
                                }
                                updateDropdown("manager", ticket.manager_id);
                            }

                            $("#ticketTitle").val(ticket.name);
                            $("#ticketDesc").val(ticket.description);
                            if (ticket.due_date) {
                                $('#datetimepicker').datepicker('setDate', new Date(ticket.due_date));
                            } else {
                                $('#datetimepicker').datepicker('setDate', null);
                            }

                            $("#ticketCreationDate").html(prettyDate(ticket.creation_date));
                            $("#ticketCreator").html(ticket.creator.name);
                            if (ticket.editor) {
                                $("#ticketEdited").css("display", "inline");
                                $("#ticketEditionDate").html(prettyDate(ticket.edition_date));
                                $("#ticketEditor").html(ticket.editor.name);
                            } else {
                                $("#ticketEdited").css("display", "none");
                            }
                            cancel_save = false;
                            removeLoading();
                            $("#informations").css("display", "block");
                        },
                    });
                }
            });
        }

        //update the given information in the database
        function saveInfo(data) {
            if (!cancel_save && ticket_id) {
                ajax_post({
                    url: "/api/ticket/" + ticket_id + "/edit",
                    data: data,
                    success: function(content) {
                        notify("<b>Success</b> changes saved !", "success");
                    }
                });
            }
        }

        //a change has occured on a dropdown
        function changeDropdown(ddtype, val) {
            switch (ddtype) {
                case "status":
                    saveInfo({
                        state: val
                    });
                    break;
                case "type":
                    saveInfo({
                        type: val
                    });
                    break;
                case "priority":
                    saveInfo({
                        priority: val
                    });
                    break;
                case "manager":
                    saveInfo({
                        manager_id: val == 0 ? null : val
                    });
                    break;
            }
        }

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-ticketTitle" class="form-group row form-custom" style="font-size:2em;">
                <label id="ticket-id" class="col-form-label" style="margin-left:0.4em"></label>
                <div class="col-6">
                    <input id="ticketTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off"> </div>
            </form>
            <div id="informations" style="display:none;">
                <h4 style="margin-top:-0.8em;"><small>Created <span id="ticketCreationDate" class="text-success"></span> by <span id="ticketCreator" class="text-primary"></span><span id="ticketEdited"> - Edited <span id="ticketEditionDate" class="text-success"></span> by <span id="ticketEditor" class="text-primary"></span></span></small></h4>


                <div class="row">
                    <h5 class="col-md-4">Status :
                        <div class="dropdown" id="dd-status"></div>
                    </h5>
                    <h5 class="col-md-4">Manager :
                        <div class="dropdown" id="dd-manager"></div>
                    </h5>
                    <h5 class="col-md-4">Type :
                        <div class="dropdown" id="dd-type"></div>
                    </h5>
                </div>
                <div class="form-group row">
                    <h5 class="col-md-4">Priority :
                        <div class="dropdown" id="dd-priority"></div>
                    </h5>
                    <label class="h5 col-form-label" for="datetimepicker" style="margin-left:15px;">Due date :</label>
                    <div class="input-group date col-md-3 col-8" data-date-format="mm/dd/yyyy" data-provide="datepicker" id="datetimepicker">
                        <input type="text" readonly class="form-control" placeholder="No date">
                        <div class="input-group-addon">
                            <span class="fa fa-calendar"></span>
                        </div>
                    </div>
                    <button id="btnDelete" class="btn btn-outline-danger offset-md-1" style="cursor:pointer;display:none;height:3em;"><i class="fa fa-trash"></i> Delete</button>

                </div>
                <form id="form-ticketDesc" class="form-group row form-custom">
                    <div class="col-sm-11">
                        <textarea id="ticketDesc" class="form-control form-control-plaintext" readonly type="text" placeholder="Description..." autocomplete="off" wrap="hard" rows="3" maxlength="4096"></textarea>
                    </div>
                </form>
                <!--<h3>Comments</h3>
                <h4 class="text-danger"><small>TODO</small></h4>-->
            </div>
        </div>
    </div>
</body>

</html>
