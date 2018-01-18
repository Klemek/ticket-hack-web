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
        var page = 1,
            page_number = 8;

        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navTickets").addClass("active");

            $("#new-ticket").click(function() {
                var win = window.open("/ticket/new", '_blank');
                win.focus();
            });

            loadList();

            setInterval(loadList, 5 * 60 * 1000);


        });

        function loadList() {
            $("#ticketList").empty();
            $("#new-ticket").css("display", "none");
            addLoading("#ticketList");
            ajax_get({
                url: "/api/ticket/list",
                data: {
                    number: page_number,
                    offset: (page - 1) * page_number
                },
                success: function(content) {
                    content.list.forEach(function(ticket) {
                        addTicket(getTicketName(ticket), ticket.name, ticket.type, ticket.priority, ticket.state, ticket.manager ? ticket.manager.name : "");
                    });
                    $("#new-ticket").css("display", "block");
                    removeLoading();

                    var maxpage = content.total / page_number;
                    if (floor(maxpage) !== maxpage)
                        maxpage = floor(maxpage) + 1;

                    $('#pagination').pagination({
                        pages: maxpage,
                        currentPage: page,
                        cssStyle: 'light-theme',
                        onPageClick: function(num) {
                            page = num;
                            loadList();
                        }
                    });
                },
                error: function(code, data) {
                    removeLoading();
                }
            });
        }

        function ticket_click(id) {
            var win = window.open("/ticket/" + id, '_blank');
            win.focus();
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
            <div class="row" style="margin-top:10px;">
                <div id="pagination" class="col-12 text-center"></div>
            </div>
        </div>
    </div>
</body>

</html>
