<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Ticket'Hack</title>
    <?php include("./template/head.php") ?>
</head>

<body>
    <?php include("./template/connected-nav.php") ?>
    <script>
        function ticket_click(id) {
            console.log("ticket-click:" + id);
            var win = window.open("./ticket-edit", '_blank');
            win.focus();
        }

        function loadList() {
            $("#ticketList").empty();
            $("#new-ticket").css("display", "none");
            addLoading("#ticketList");
            ajax_get({
                url: "/api/ticket/list",
                success: function(content) {
                    $("#new-ticket").css("display", "block");
                    removeLoading();
                },
                error: function(code, data) {
                    removeLoading();
                }
            });
        }

        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navTickets").addClass("active");

            loadList();

            $("#new-ticket").click(function() {

            });

        });

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
