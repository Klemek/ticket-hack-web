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

            var id = window.location.href.split("/project/")[1].toUpperCase();
            if (id.indexOf("/") !== -1) {
                writeCookie("notify", "warning-Invalid project id.", 1);
                window.location = "./projects";
                return;
            }

            $("#project-id").html("<b>[" + id + "]</b>");
            $("#projectTitle").val("Loading...");
            addLoading(".jumbotron");

            /*
            registerCustomInput("projectTitle", false, function() {
                console.log("todo save projectTitle");
            });
            */

            $("#new-ticket").click(function() {
                var win = window.open("/ticket/new", '_blank');
                win.focus();
            });
        });

        function ticket_click(id) {
            var win = window.open("/ticket/" + id, '_blank');
            win.focus();
        }

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-projectTitle" class="form-group row form-custom" style="font-size:2em;">
                <label id="project-id" class="col-form-label" style="margin-left:0.4em"></label>
                <div class="col-sm-6">
                    <input id="projectTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off">
                </div>
            </form>
            <div id="informations" style="display:none;">
                <h4 style="margin-top:-0.8em;"><small>Created the 8th January 2018 by <a href="#">John ROBERT</a> - Edited the 9th January 2018 by <a href="#">Donald CHARLES</a></small></h4>
                <h3>Associated tickets</h3>
                <div id="ticketList"></div>
                <div class="ticket new-ticket" id="new-ticket"> <span title="improvement" class="fa-stack type">
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
