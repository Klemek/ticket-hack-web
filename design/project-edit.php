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

            $("#navProjects").addClass("active");
            $("#dropdownUser").html(randString(fakeUserNames));

            registerCustomInput("projectTitle", false, function() {
                console.log("todo save projectTitle");
            });

            var project = randString(fakeProjectNames);
            $("#project-id").html("<b>[" + project + "]</b>");
            $("#projectTitle").val(randString(fakeProjectDesc));

            $("#new-ticket").click(function() {
                addFakeTicket(project);
            });
            for (var i = 0; i < 5; i++) {
                $("#new-ticket").click();
            }
        });

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-projectTitle" class="form-group row form-custom" style="font-size:2em;">
                <label id="project-id" class="col-form-label" style="margin-left:0.4em"></label>
                <div class="col-sm-6">
                    <input id="projectTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off">
                </div>
            </form>
            <h4 style="margin-top:-0.8em;"><small>Created the 8th January 2018 by <a href="#">John ROBERT</a> - Edited the 9th January 2018 by <a href="#">Donald CHARLES</a></small></h4>
            <h3>Sub categories</h3>
            <h4 class="text-danger"><small>TODO</small></h4>
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
</body>

</html>
