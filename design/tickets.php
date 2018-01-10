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
        var ticketNames = [
            'randomly generated ticket', 'a random ticket', 'some ticket', 'omg a ticket', 'a nice ticket', 'you should open this one'
        ];
        var userNames = [
            'John ROBERT', 'Joseph DAVID', 'Donald CHARLES', 'Michael WILLIAMS', '', '', '', ''
        ];

        function addTicket(name, desc, type, priority, user) {
            if (user.length > 0) user = '<h5 class="text-primary">' + user + '</h5>';
            var html = '<div class="ticket" onclick="ticket_click(\'' + name + '\')">' + '<span title="' + type_titles[type] + '" class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span>' + '<i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + ' priority" title="priority : ' + priority_titles[priority] + '"></i>' + user + '<h4>' + name + ' <small>' + desc + '</small></h4></div>';
            $("#ticketList").append(html);
        }

        function ticket_click(id) {
            console.log("ticket-click:" + id);
            var win = window.open("./ticket-edit", '_blank');
            win.focus();
        }

        $(document).ready(function() {

            $("#navTickets").addClass("active");

            $("#new-ticket").click(function() {
                addTicket("TEST-" + pad(randInt(1, 999), 3), randString(ticketNames), randInt(0, 2), randInt(0, 4), randString(userNames));
            });

            for (var i = 0; i < 5; i++) {
                $("#new-ticket").click();
            }


        });

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <h1>Tickets</h1>
            <div id="ticketList"> </div>
            <div class="ticket new-ticket" id="new-ticket">
                <span class="fa-stack type">
                  <i class="fa fa-square fa-stack-2x"></i>
                  <i class="fa fa-plus fa-stack-1x fa-inverse"></i>
                </span>
                <h4>Open a new ticket</h4>
            </div>
        </div>
    </div>
</body>

</html>
