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

            loadList();

            $("#new-ticket").click(function() {
                var win = window.open("/ticket/new", '_blank');
                win.focus();
            });

        });

        function ticket_click(id) {
            var win = window.open("/ticket/" + id, '_blank');
            win.focus();
        }

        function loadList() {
            $("#ticketList").empty();
            $("#new-ticket").css("display", "none");
            addLoading("#ticketList");
            ajax_get({
                url: "/api/ticket/list",
                success: function(content) {
                    content.forEach(function(ticket) {
                        addTicket(ticket.simple_id, ticket.name, ticket.type, ticket.priority, ticket.state, ticket.manager_id);
                    });
                    $("#new-ticket").css("display", "block");
                    removeLoading();

                },
                error: function(code, data) {
                    removeLoading();
                }
            });
        }

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <h1>Tickets</h1>
            <div id="ticketList">
            </div>
            <div class="ticket new-ticket" id="new-ticket" style="display:none;">
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
