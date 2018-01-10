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
        function randInt(min, max) {
            return Math.floor((Math.random() * (max + 1)) + min);
        }

        function addProject(name, desc) {
            var html = '<div class="project" onclick="project_click(\'' + name + '\')">' + '<h4>' + name + ' <small>' + desc + '</small></h4></div>';
            $("#projectList").append(html);
        }

        function project_click(id) {
            console.log("project click:" + id);
            var win = window.open("./project-edit", '_blank');
            win.focus();
        }

        $(document).ready(function() {

            $("#navProjects").addClass("active");

            $("#new-project").click(function() {
                addProject("TEST", "test project (" + randInt(2, 1000000) + " tickets)");
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
