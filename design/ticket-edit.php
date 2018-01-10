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
            $("#navTickets").addClass("active");

            registerCustomInput("ticketTitle", false, function() {
                console.log("todo save ticketTitle");
            });
            registerCustomInput("ticketDesc", true, function() {
                console.log("todo save ticketDesc");
            });

            for (var type = 0; type < 3; type++) {
                $("#dropdownTypeMenu").append('<a class="dropdown-item" href="#"><span class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span> ' + type_titles[type] + '</a>');
            }

            for (var priority = 0; priority < 5; priority++) {
                $("#dropdownPriorityMenu").append('<a class="dropdown-item" href="#"><i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + '"></i> ' + priority_titles[priority] + '</a>');
            }

        });

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <form id="form-ticketTitle" class="form-group row form-custom" style="font-size:2em;">
                <label class="col-form-label" style="margin-left:0.4em"><b>[TEST-001]</b></label>
                <div class="col-sm-6">
                    <input id="ticketTitle" class="form-control form-control-lg form-control-plaintext" readonly type="text" placeholder="Title" required autocomplete="off" value="Test ticket"> </div>
            </form>
            <h4 style="margin-top:-0.8em;"><small>Created the 8th January 2018 by <a href="#">John ROBERT</a> - Edited the 9th January 2018 by <a href="#">Donald CHARLES</a></small></h4>
            <h5>Type :
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownType" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><span title="improvement" class="fa-stack text-success"><i class="fa fa-square fa-stack-2x"></i><i class="fa fa-arrow-up fa-stack-1x fa-inverse"></i></span> Improvement</button>
                    <div id="dropdownTypeMenu" class="dropdown-menu" aria-labelledby="dropdownType"></div>
                </div>&nbsp;&nbsp;&nbsp;Manager : <a href="#">John ROBERT</a>&nbsp;&nbsp;&nbsp;Priority :
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" id="dropdownPriority" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="fa fa-thermometer-0 text-success"></i> Lowest</button>
                    <div id="dropdownPriorityMenu" class="dropdown-menu" aria-labelledby="dropdownPriority"></div>
                </div>
            </h5>
            <form id="form-ticketDesc" class="form-group row form-custom">
                <div class="col-sm-11">
                    <textarea id="ticketDesc" class="form-control form-control-plaintext" readonly type="text" placeholder="Description..." autocomplete="off" wrap="hard" rows="3" maxlength="4096">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nam in volutpat mauris, non finibus nulla. Vestibulum tincidunt diam ut magna efficitur tincidunt. Maecenas vitae sodales mi, non dictum dolor. Nullam imperdiet purus at magna aliquam, et tincidunt purus volutpat. Praesent nec nulla feugiat, placerat nisi in, interdum enim. Ut in vehicula nibh. Vivamus ullamcorper pellentesque arcu a mattis. Nulla a mi est. Suspendisse nec tincidunt elit, eu rutrum ante. Aenean ante mi, elementum in bibendum non, malesuada ac mauris.</textarea>
                </div>
            </form>
            <h3>Comments</h3>
            <h4 class="text-danger"><small>TODO</small></h4>
        </div>
    </div>
</body>

</html>
