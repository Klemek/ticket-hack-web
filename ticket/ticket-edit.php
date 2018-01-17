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

            var id = window.location.href.split("/ticket/")[1].toUpperCase();
            if (id.indexOf("/") !== -1 || id.length > 4) {
                writeCookie("notify", "warning-Invalid ticket id.", 1);
                window.location = "/tickets";
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
            });*/

            initDropdown("dd-status", "status", 0);
            initDropdown("dd-type", "type", 0);
            initDropdown("dd-priority", "priority", 0);

        });

        function changeStatus(status) {

        }

        function changeType(type) {

        }

        function changePriority(priority) {

        }

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
                        <div class="dropdown" id="dd-status"></div>
                    </h5>
                    <h5 class="col-sm-5">Manager : <a href="#">John ROBERT</a></h5>
                </div>
                <div class="row">
                    <h5 class="col-sm-3">Type :
                        <div class="dropdown" id="dd-type"></div>
                    </h5>
                    <h5 class="col-sm-3">Priority :
                        <div class="dropdown" id="dd-priority"></div>
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
