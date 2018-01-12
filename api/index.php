<?php
/** Ticket'Hack API
* enables to access to projects, tickets and users with web commands
* 
* root = "ticket'hack.com/api/";
* USER:
* - /user/ access to users data
*   /user/{id} get the json output of the database for the user at the id {id}
*   /user/{id}/projects get the projects the user has access to.
*   /user/me  shortcut to /user/{my id} for authentified users.
*   /user/new adds a user : it neeed POST parameters
*      -name
*      -email
*      -password
*
* PROJECT
* - /project/ access to the projects data
*   /project/{id} get the data of the project if the user has the right to access it.
*   /project/{id}/delete delete the project; only an admin or a maximum level user on this project can use this.
*   /project/{id}/adduser add a user to the project; POST parameters
*      -id_user
*      -access_level
*      please note this function can only be used by someone with higher access level than 0 AND he cannot gives higher clearance to someone else.
*   /project/{id}/removeuser remove a user; POST parameters
*      -id_user
*
*
*   /project/{id}/addticket add a ticket to the project. POST parameters : 
*      -title
*      -priority 
*      -description 
*      -due_date
*
*      returns the ticket as it is in the database. user need to be identificated
*
*   /project/{id}/tickets return tickets of the project
*   /project/{id}/ticket/{id_simple_ticket} return the ticket
*   /project/add add a project; POST parameters
*      -name
*      -ticket_prefix
*      
*      returns th project as it is in the db
* 
* TICKETS
*   /ticket/{id} return the ticket information IF the user has access to the project
                 equivalent to /project/{id}/ticket/{id_simple_ticket}. all the following can be used on both path
*   /ticket/{id}/comments get the comments of the ticket
*   /ticket/{id}/comment/{id_comment} return the comment detail
*   /ticket/{id}/comment/{id_comment}/remove
*   /ticket/{id}/comment/{id_comment}/edit parametre POST
*      -comment
*   /ticket/{id}/addcomment POST
*      -comment
*      user needs to be authenticated
*
* COMMENTS
* /comment/{id}
* /comment/{id}/remove
* /comment/{id}/edit
*    -comment
* 
**/


require_once "router.php";
require_once "../functions.php";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

header('Content-type: application/json');

function get($str, $optionnal = false){
    if (isset($_GET[$str])){
        return $_GET[$str];
    }

    if (! $optionnal){
        http_error(400, "missing GET parameter");
    }

    return null;
}

function post($str, $optionnal = false){
    if (isset($_POST[$str])){
        return $_POST[$str];
    }
    if (! $optionnal){
        http_error(400, "missing POST parameter");
    }

    return null;
}

/**
* generate an error and clsoe the programm
* @param code the http code
* @param msg the message you want to display
* @param args additionnal output you may want to display
*
*/
function http_error($code, $msg, $args = array()){
    http_response_code($code);
    $output = array(
        "status"=>$code,
        "result"=>"error",
        "error"=>$msg
    );

    foreach ($args as $t=>$v){
        $output["$t"] = $v;
    }

    echo json_encode($output);
    exit;
}

/**
* return the user id if he is connected
* kill the process otherwise
**/
function force_auth(){
    if (isset($_SESSION["user_id"])){
        return $_SESSION["user_id"];
    }else{
        http_error(401, "You need to be authentified to access this method");
    }
}

$route = new Route();
function route(...$args){global $route;$route->route(...$args);};

/*-----------------------------------------------------------------------------------------------------------------------------------------*/

/** GENERAL
* /login login a user - return truthy or falsey depending on if the user successfully connected
*     - POST:email
*     - POST:password
* /disconnext disconnect a user - clear all cookies and delete the session
*
**/
$route->post(array("/api/login",
                   "/api/user/login"), function(){
    $mail = post("email");
    $password = post("password");

    if (validate_user($mail, $password)){
        $_SESSION["user_id"] = get_user_by_email($mail)["id"];
        echo "user logged in";
    }else{    
        echo "login failed";
    }
});

$route->route(array("/api/logout",
                    "/api/disconnect",
                    "/api/user/logout",
                    "/api/user/disconnect"), function(){
    if (session_status() == PHP_SESSION_ACTIVE) { session_destroy(); }
});



/**
* USER:
* - /user/ access to users data
*   /user/{id} get the json output of the database for the user at the id {id}
*   /user/{id}/projects get the projects the user has access to.
*   /user/me  shortcut to /user/{my id} for authentified users.
*   /user/add adds a user : it neeed POST parameters
*      -name
*      -email
*      -password
**/
$route->post(array("/api/user/new",
                   "/api/user/add"), function(){
    $name = post("name");
    $email = post("email");
    $password = post("password");

    if (user_test_mail($email)){
        http_error(405,"email already taken");
    }

    $id_user = add_user($name, $email, $password);

    $output = array("id_user"=>$id_user);
    echo json_encode($output);
});

//todo : check this function
route("/api/user/me", function(){    
    $id = force_auth();

    $output = get_user($id);
    echo json_encode($output);
});

$route->get("/api/user/{id}", function($id){
    $id = (int) $id;
    $output = get_user($id);
    echo json_encode($output);
});

/** edit the user info - POST
* POST: user_id
* optional parameter : password, name, email
*
* @return the user infos
**/
//todo : add a security
$route->post(array("/api/user/{id}/edit",
                   "/api/user/me/edit"), function($id = null){
    $id = ($id !== null) ? (int) $id : force_auth();
    $name = post("name", true);
    $email = post("email", true);
    $password = post("password", true);

    $args = array(":id"=>$id);
    $set = array();

    if ($name){
        $args[":name"] = $name;
        $set[]="name = :name";
    }

    if ($email){
        $args[":email"] = $email;
        $set[]="email = :email";
    }

    if ($password){
        $args[":password"] = hash_passwd($password);
        $set[]="password = :password";
    }

    if (count($set) >= 1){
        $req = "UPDATE users SET ".join(",",$set)." WHERE id=:id";
        execute($req, $args);
    }


    $output = get_user($id);
    echo json_encode($output);
});

$route->delete(array("/api/user/me/delete",
                     "/api/user/{id}/delete"),
               function($id = 0){
                   $id = $id !== 0 ? (int) $id : force_auth();

                   //todo : add more security
                   if (isset($_SESSION["user_id"]) && $id == $_SESSION["user_id"]){
                       delete_user($id);
                       if ($id == $_SESSION["user_id"]){
                           session_destroy();
                       }
                   }else{
                       echo $id." - ".$_SESSION["user_id"]."\n";
                       echo "you cannot destroy an account if it is not yours / you are not connected";
                   }

                   $output = array();
                   echo json_encode($output);
               });

//todo check here
$route->route(array("/api/user/{id}/projects",
                    "/api/user/me/projects",
                    "/api/projects"), function($id = null){

    $id = ($id === null) ? force_auth() : (int) $id;
    $output = get_projects_for_user($id);
    echo json_encode($output);
});

/**
* PROJECT:
* - /project/ access to the projects data
*   /project/{id} get the data of the project if the user has the right to access it.
*   /project/{id}/delete delete the project; only an admin or a maximum level user on this project can use this.
*   /project/{id}/adduser add a user to the project; POST parameters
*      -id_user
*      -access_level
*      please note this function can only be used by someone with higher access level than 0 AND he cannot gives higher clearance to someone else.
*   /project/{id}/removeuser remove a user; POST parameters
*      -id_user
*
*
*   /project/{id}/addticket add a ticket to the project. POST parameters : 
*      -title
*      -priority 
*      -description 
*      -due_date
*
*      returns the ticket as it is in the database. user need to be identificated
*
*   /project/{id}/tickets return tickets of the project
*   /project/{id}/ticket/{id_simple_ticket} return the ticket
*   /project/new add a project; POST parameters
*      -name
*      -ticket_prefix
*      
*      returns th project as it is in the db
**/

$route->post("/api/project/new", function(){
    $name = post("name");
    $ticket_prefix = post("ticket_prefix");
    $id_user = force_auth();

    $id_project = add_project($name,$id_user, $ticket_prefix);

    $output = array("id_project"=>$id_project);
    echo json_encode($output);
});


$route->get("/api/project/{id}", function($id){
    $id = (int) $id;
    $project = get_project($id);
    echo json_encode($project);
});

/*only the creator can change these parameters for now*/
$route->post("/api/project/{id}/edit", function($id){
    $name = post("name", true);
    $ticket_prefix = post("ticket_prefix", true);
    $id_user = force_auth();

    $args = array(":id"=>$id,
                  ":creator_id"=>$id_user);
    $set = array();

    if ($name){
        $args[":name"] = $name;
        $set[]="name = :name";
    }

    if ($ticket_prefix){
        $args[":ticket_prefix"] = $ticket_prefix;
        $set[]="ticket_prefix = :ticket_prefix";
    }

    if (count($set) >= 1){
        $req = "UPDATE projects SET ".join(",",$set)." WHERE id=:id AND creator_id=:creator_id;";
        execute($req, $args);
    }

    $output = get_project($id);
    echo json_encode($output);
});

/*only the creator can delete for now*/
$route->delete("/api/project/{id}/delete", function($id){
    $id = (int) $id;
    $id_user = force_auth();

    if (is_admin($id_user,$id)){
        delete_project($id);
    }else{
        echo "error - you do not have the right access level to do that";
    }
});

//todo : add user verification and add a return
$route->post("/api/project/{id}/adduser", function($id_project){
    $id_project = (int) $id_project;
    //todo : add verification that the logged user CAN do it
    $id_user = (int) post("id_user");
    $access_level = (int) post("access_level");

    add_link_user_project($id_user, $id_project, $access_level);    
});

/*return the users on the project*/
//todo : verify if the user has the right to see that
$route->get("/api/project/{id}/users", function($id){
    $id = (int) $id;
    echo json_encode(get_users_for_project($id));
});

//todo : verify the user CAN do it
$route->post("/api/project/{id}/removeuser", function($id_project){
    $id_project = (int) $id_project;
    //todo : add verification that the logged user CAN do it
    $id_user = (int) post("id_user");

    delete_link_user_project($id_user, $id_project);
});

//todo : verify theuser can do it
$route->post("/api/project/{id}/addticket", function($id_project){
    $id_project = (int) $id_project;
    $title = post("title");
    $priority = post("priority");
    $description = post("description");
    $due_date = post("due_date");

    $creator_id = force_auth();

    $id = add_ticket($title, $id_project, $creator_id, $creator_id, $priority, $description, $due_date);

    $output = array("id_ticket" => $id);
    echo json_encode($output);    
});

//todo : add false case and vverify if user has the right to check this
$route->get("/api/project/{id}/tickets", function($id_project){
    echo json_encode(get_tickets_for_project($id_project));
});

//todo : add false case
$route->get("/api/project/{id_project}/ticket/{id_simple_ticket}", function($id_project, $id_simple_ticket){
    echo json_encode(get_ticket_simple($id_project, $id_simple_ticket));
});


/**
* TICKETS
*   /ticket/{id} return the ticket information IF the user has access to the project
                 equivalent to /project/{id}/ticket/{id_simple_ticket}. all the following can be used on both path
*   /ticket/{id}/comments get the comments of the ticket
*      -comment
*   /ticket/{id}/addcomment POST
*      -comment
*      user needs to be authenticated
**/

$route->get("/api/ticket/{id}", function($id){
    echo json_encode(get_ticket($id));
});

/*todo : check the user can do that*/
$route->post("/api/ticket/{id}/edit", function($id_ticket){
    $id_ticket = (int) $id_ticket;
    $params = array(
        "name"=>post("name", true),
        "priority"=>post("priority", true),
        "description"=>post("description", true),
        "due_date"=>post("due_date", true)
    );

    $args = array(":ticket_id"=>$id_ticket);
    $set = array();

    foreach($params as $t=>$v){
        if ($v){
            $args[":$t"] = $v;
            $set[] = "$t = :$t";
        }
    }

    if (count($set) >= 1){
        $req = "UPDATE tickets SET ".join(",",$set)." WHERE id=:ticket_id;";
        execute($req, $args);
    }

    echo json_encode(get_ticket($id_ticket));
});

/*todo : check the user can do that*/
$route->delete("/api/ticket/{id}/delete", function($id_ticket){
    delete_ticket($id_ticket);
});

/*todo : check the user can do that AND that the ticket exists*/
$route->post("/api/ticket/{id}/addcomment", function($ticket_id){
    $ticket_id = (int) $ticket_id;
    $comment = post("comment");
    $creator_id = force_auth();

    $id = add_comment($ticket_id, $creator_id, $comment);
    $output = array("id_comment"=>$id);
    echo json_encode($output);
});

$route->get("/api/ticket/{id}/comments", function($id){
    echo json_encode(get_comments_for_ticket($id));
});


/**
* COMMENTS
* GET /api/comment/{id}
* POST /api/comment/{id}/edit
*    -comment
* DELETE /api/comment/{id}/delete
*
**/
//todo : check if the user has rights
$route->get("/api/comment/{id_comment}", function($id_comment){
    echo json_encode(get_comment($id_comment));
});

$route->post("/api/comment/{id}/edit", function($comment_id){
    $comment = post("comment");
    $creator_id = force_auth();

    if (get_comment($comment_id)["creator_id"] != $creator_id){
        http_error(403,"You cannot modify this comment as it isn't yours");
    }

    $id = edit_comment($comment_id, $comment);

    echo json_encode(get_comment($comment_id));
});

$route->delete("/api/comment/{id}/delete", function($comment_id){
    $comment_id = (int) $comment_id;
    $id_user = force_auth();
    $id_project = execute("SELECT project_id FROM tickets WHERE id IN (SELECT ticket_id FROM comments WHERE id = ?)", array($comment_id))->fetch()["project_id"];

    if (is_admin($id_user, $id_project) || get_comment($comment_id)["creator_id"] == $creator_id){
        delete_comment($comment_id);
    }
});

/**
* Error Handling
*
*
**/
$route->error_404(function(){
    http_response_code(404);
    $output = array(
        "error_code" => 404,
        "message" => "404 - the server couldn't find a page corresponding to your url and method settings"
    );
    echo json_encode($output);
    exit;
});
?>