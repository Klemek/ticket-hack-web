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
        function changeStatus(status) {
            $("#dropdownStatus").html('<i class="fa ' + status_icons[status] + ' "></i> ' + status_titles[status]);
        }

        function changeType(type) {
            $("#dropdownType").html('<span class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span> ' + type_titles[type]);
        }

        function changePriority(priority) {
            $("#dropdownPriority").html('<i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + '"></i> ' + priority_titles[priority]);
        }



        $(document).ready(function() {

            initNotification(".jumbotron");
            $("#navTickets").addClass("active");

            var id = window.location.href.split("/ticket/")[1].toUpperCase();
            if (id.indexOf("/") !== -1 || id.length > 4) {
                writeCookie("notify", "warning-Invalid ticket id.", 1);
                window.location = "./tickets";
                return;
            }

            $("#ticket-id").html("<b>[" + id + "]</b>");
            $("#ticketTitle").val("Loading...");
            addLoading(".jumbotron");

            /*
            registerCustomInput("ticketTitle", false, function() {
                console.log("todo save ticketTitle");
            });
            registerCustomInput("ticketDesc", true, function() {
                console.log("todo save ticketDesc");
            });
            
            changeStatus(randInt(0, 3));
            changePriority(randInt(0, 4));
            changeType(randInt(0, 2));

            for (var status = 0; status < 4; status++) {
                $("#dropdownStatusMenu").append('<a class="dropdown-item" href="#" onclick="changeStatus(' + status + ')"><i class="fa ' + status_icons[status] + ' "></i> ' + status_titles[status] + '</a>');
            }

            for (var type = 0; type < 3; type++) {
                $("#dropdownTypeMenu").append('<a class="dropdown-item" href="#" onclick="changeType(' + type + ')"><span class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span> ' + type_titles[type] + '</a>');
            }

            for (var priority = 0; priority < 5; priority++) {
                $("#dropdownPriorityMenu").append('<a class="dropdown-item" href="#" onclick="changePriority(' + priority + ')"><i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + '"></i> ' + priority_titles[priority] + '</a>');
            }*/

        });

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-ticketTitle" class="form-group row form-custom" style="font-size:2em;">
                <label id="ticket-id" class="col-form-label" style="margin-left:0.4em"></label>
                <div class="col-sm-6">
                    <input id="ticketTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off"> </div>
            </form>
            <div id="informations" style="display:none;">
                <h4 style="margin-top:-0.8em;"><small>Created the 8th January 2018 by <a href="#">John ROBERT</a> - Edited the 9th January 2018 by <a href="#">Donald CHARLES</a></small></h4>
                <div class="row">
                    <h5 class="col-sm-3">Status :
                        <div class="dropdown">
                            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownStatus" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                            <div id="dropdownStatusMenu" class="dropdown-menu" aria-labelledby="dropdownStatus"></div>
                        </div>
                    </h5>
                    <h5 class="col-sm-5">Manager : <a href="#">John ROBERT</a></h5>
                </div>
                <div class="row">
                    <h5 class="col-sm-3">Type :
                        <div class="dropdown">
                            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownType" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                            <div id="dropdownTypeMenu" class="dropdown-menu" aria-labelledby="dropdownType"></div>
                        </div>
                    </h5>
                    <h5 class="col-sm-3">Priority :
                        <div class="dropdown">
                            <button class="btn btn-default dropdown-toggle" type="button" id="dropdownPriority" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button>
                            <div id="dropdownPriorityMenu" class="dropdown-menu" aria-labelledby="dropdownPriority"></div>
                        </div>
                    </h5>
                </div>
                <form id="form-ticketDesc" class="form-group row form-custom">
                    <div class="col-sm-11">
                        <textarea id="ticketDesc" class="form-control form-control-plaintext" readonly type="text" placeholder="Description..." autocomplete="off" wrap="hard" rows="3" maxlength="4096">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam in volutpat mauris, non finibus nulla. Vestibulum tincidunt diam ut magna efficitur tincidunt. Maecenas vitae sodales mi, non dictum dolor. Nullam imperdiet purus at magna aliquam, et tincidunt purus volutpat. Praesent nec nulla feugiat, placerat nisi in, interdum enim. Ut in vehicula nibh. Vivamus ullamcorper pellentesque arcu a mattis. Nulla a mi est. Suspendisse nec tincidunt elit, eu rutrum ante. Aenean ante mi, elementum in bibendum non, malesuada ac mauris.</textarea>
                    </div>
                </form>
                <h3>Comments</h3>
                <h4 class="text-danger"><small>TODO</small></h4>
            </div>
        </div>
    </div>
</body>

</html>
