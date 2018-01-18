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
            $("#navProjects").addClass("active");

            $("#new-project").click(function() {
                var win = window.open("/project/new", '_blank');
                win.focus();
            });

            loadList();

            setInterval(loadList, 5 * 60 * 1000);

        });

        function loadList() {
            $("#projectList").empty();
            $("#new-project").css("display", "none");
            addLoading("#projectList");
            ajax_get({
                url: "/api/project/list",
                success: function(content) {
                    $("#new-project").css("display", "block");
                    removeLoading();
                    content.list.forEach(function(project) {
                        addProject(project.id, project.ticket_prefix, project.name);
                    });
                },
                error: function(code, data) {
                    removeLoading();
                }
            });
        }

        function project_click(id, simple_id) {
            writeCookie("project_id", id, 1);
            var win = window.open("/project/" + simple_id, '_blank');
            win.focus();
        }

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
