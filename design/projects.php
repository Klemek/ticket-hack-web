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
        function project_click(id) {
            console.log("project click:" + id);
            var win = window.open("./project-edit", '_blank');
            win.focus();
        }

        $(document).ready(function() {
            initNotification(".jumbotron");
            $("#navProjects").addClass("active");
            $("#dropdownUser").html(randString(fakeUserNames));

            $("#new-project").click(function() {
                addFakeProject();
            });

            $("#new-project").click();

        });

    </script>
    <div class="container">
        <div class="jumbotron primary">
            <h1>Projects</h1>
            <div id="projectList"></div>
            <div class="project new-project" id="new-project">
                <span class="fa-stack type">
                  <i class="fa fa-square fa-stack-2x"></i>
                  <i class="fa fa-plus fa-stack-1x fa-inverse"></i>
                </span>
                <h4>Create a new project</h4>
            </div>
        </div>
    </div>
</body>

</html>
