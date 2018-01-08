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
*   /user/add adds a user : it neeed POST parameters
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


require_once "routage.php";
require_once "../functions.php";

if(!isset($_COOKIE["PHPSESSID"]))
{
    session_start();
}

header('Content-type: application/json');

function get($str){
    if (isset($_GET[$str])){
        return $_GET[$str];
    }

    die("missing GET parameter - $str");
}

function post($str){
    if (isset($_POST[$str])){
        return $_POST[$str];
    }

    die("missing POST parameter - $str");
}



/*-----------------------------------------------------------------------------------------------------------------------------------------*/

/** GENERAL
* /login login a user - return truthy or falsey depending on if the user successfully connected
*     - POST:email
*     - POST:password
* /disconnext disconnect a user - clear all cookies and delete the session
*
**/
route("/api/login", function(){
    $mail = post("email");
    $password = hash_passwd(post("password"));

    if (validate_user($mail, $password)){
        $_SESSION["user_id"] = get_user_by_email($mail)["id"];


    }
});

route("/api/disconnect", function(){
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

//todo : check this function
route("/api/user/add", function(){
    $name = post("name");
    $email = post("email");
    $password = post("password");

    if (user_test_mail($email)){
        //todo : add 409 error
        die();
    }

    $id_user = add_user($name, $email, $password);

    $output = array("id_user"=>$id_user);
    echo json_encode($output);
});

//todo : check this function
route("/api/user/me", function(){    
    $id = $_SESSION["user_id"];

    $output = get_user($id);
    echo json_encode($output);
});

route("/api/user/{id}", function($id){
    $id = (int) $id;
    $output = get_user($id);
    echo json_encode($output);
});

route("/api/user/{id}/projects", function($id){
    $id = (int) $id;
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
*   /project/add add a project; POST parameters
*      -name
*      -ticket_prefix
*      
*      returns th project as it is in the db
**/

route("/api/project/{id}", function($id){
    $id = (int) $id;
    $project = get_project($id);
    echo json_encode($project);
});

route("/api/project/{id}/delete", function($id){
    $id = (int) $id;
    //todo : add verification that the logged user CAN do it
    delete_project($id);
});

//todo : check this function
route("/api/project/{id}/adduser", function($id_project){
    $id_project = (int) $id_project;
    //todo : add verification that the logged user CAN do it
    $id_user = (int) post("id_user");
    $access_level = (int) post("access_level");

    add_link_user_project($id_user, $id_project, $access_level);    
});

//todo : check this function
route("/api/project/{id}/removeuser", function($id_project){
    $id_project = (int) $id_project;
    //todo : add verification that the logged user CAN do it
    $id_user = (int) post("id_user");

    delete_link_user_project($id_user, $id_project);
});

route("/api/project/{id}/addticket", function($id_project){
    $id_project = (int) $id_project;
    $title = post("title");
    $priority = post("priority");
    $description = post("description");
    $due_date = post("due_date");

    $creator_id = $_SESSION["user_id"];

});





?>