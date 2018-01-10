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

            registerCustomInput("projectTitle", false, function() {
                console.log("todo save projectTitle");
            });

            $("#new-ticket").click(function() {
                addTicket("TEST-" + pad(randInt(1, 999), 3), randString(ticketNames), randInt(0, 2), randInt(0, 4), randString(userNames));
            });
            for (var i = 0; i < 5; i++) {
                $("#new-ticket").click();
            }
        });
        var ticketNames = [
            'randomly generated ticket', 'a random ticket', 'some ticket', 'omg a ticket', 'a nice ticket', 'you should open this one'
        ];
        var userNames = [
            'John ROBERT', 'Joseph DAVID', 'Donald CHARLES', 'Michael WILLIAMS', '', '', '', ''
        ];

        function addTicket(name, desc, type, priority, user) {
            if (user.length > 0) user = '<h5 class="text-primary">' + user + '</h5>';
            var html = '<div class="ticket">' + '<span title="' + type_titles[type] + '" class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span>' + '<i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + ' priority" title="priority : ' + priority_titles[priority] + '"></i>' + user + '<h4>' + name + ' <small>' + desc + '</small></h4></div>';
            $("#ticketList").append(html);
        }

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-projectTitle" class="form-group row form-custom" style="font-size:2em;">
                <label class="col-form-label" style="margin-left:0.4em"><b>[TEST]</b></label>
                <div class="col-sm-6">
                    <input id="projectTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off" value="Test project">
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
