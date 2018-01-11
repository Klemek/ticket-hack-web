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
        function ticket_click(id) {
            console.log("ticket-click:" + id);
            var win = window.open("./ticket-edit", '_blank');
            win.focus();
        }

        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navTickets").addClass("active");
            $("#dropdownUser").html(randString(fakeUserNames));

            $("#new-ticket").click(function() {
                addFakeTicket();
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
