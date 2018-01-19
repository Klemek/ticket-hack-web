<?php

/*start the session*/
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "db_connect.php";

/**
*
* @param $str = null the date in string : "2017-12-19 14:38:45.12345". not puting a date returns tha current time
*
* @return a unix timestamp
**/
function get_date($str = null){
    if ($str){
        return strtotime($str);
    }
    return time();
}

/**
* get the current date in a pgsql format
* @return the current date formated to pgsql timestamp
**/
function get_date_string(){
    return date("Y-m-d h:i:s", get_date());
}

/*-------------------------------------------------------------- USERS -------------------------------------------------------------------*/

/**
* hash the user password
* @param $pswd the password to hash
*
* @return a sha256 hash
**/
function hash_passwd($pswd){
    $salt_pre = "ticket'hack";
    $salt_post = "145698235";

    return hash('sha256',$salt_pre.$pswd.$salt_post);
}

/**
* test a mail to check if it exists within the database
* @param mail the mail to test
*
* @return boolean
**/
function user_test_mail($mail){
    $req = "SELECT COUNT(email) AS c FROM users WHERE email=?;";
    $values = array($mail);

    $result = execute($req, $values);

    return $result->fetch()["c"] >= 1;//fetchColumn diffère en 32 et 64 bits
}

/** 
* add a user
* @param name the name of the new user
* @param email the email of the new user
* @param password the password of the new user
*
* @return the id of the row inserted
*
* die if the mail already exists within the database
**/
function add_user($name, $email, $password){
    global $db;

    if (user_test_mail($email)){
        die("Error - Mail already exists in the database");
    }

    $req = "INSERT INTO users(name, email, password) VALUES (?,?,?) RETURNING id;";
    $values = array($name, $email, hash_passwd($password));

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

/**
* get the user's data, without his password
* @param id the user's id
*
* @return the user data in an object. returns false if the user doesn't exists
**/
function get_user($id){
    global $db;
    $req = "SELECT * FROM users WHERE id = ". (int) $id;

    $res = $db->query($req)->fetchall(PDO::FETCH_ASSOC);
    if (count($res)){
        $output = $res[0];
        unset($output["password"]);
        return $output;
    }
    return false;
}

/**
* get the user's data, without his password
* @param email the user's email
*
* @return the user data in an object. returns false if the user doesn't exists
**/
function get_user_by_email($email){
    global $db;
    $req = "SELECT * FROM users WHERE email = ?";
    $values = array($email);

    $res = execute($req,$values)->fetchall(PDO::FETCH_ASSOC);
    if (count($res)){
        $output = $res[0];
        unset($output["password"]);
        return $output;
    }
    return false;
}

/**
* Verify if the combination email/password exists within the database AND if the user hasn't closed his account
* @param email
* @param password the password associated to the email
*
* @return boolean
**/
function validate_user($email, $password){
    global $db;

    $req = "SELECT count(email) AS c FROM users WHERE email=? AND password=? AND deletion_date IS NULL";
    $values = array($email, hash_passwd($password));

    $result = execute($req, $values);
    return $result->fetch()["c"] >= 1; 
}

/**
* Verify if the combination email/password exists within the database and if the user hasn't tried to login too much in the last minute. uses validate_user
* @param email
* @param password the password associated to the email
*
* @return boolean
**/
function validate_user_with_fail($email, $password){
    /*check the db*/
    $i = 5;//paramètre anti-boucle infine
    $id_user = get_user_by_email($email)["id"];
    do{
        $req = "SELECT * FROM connection_history WHERE user_id = ?";
        $values = array($id_user);

        $connection = execute($req, $values)->fetch();

        if (! $connection){
            $req= "INSERT INTO connection_history(user_id) VALUES (?)";
            $values = array($id_user);
            execute($req, $values);
        }
    }while((! $connection) && $i-- > 0);

    if ($i==0){//db error - pass to simple login
        return validate_user($email, $password);
    }

    $first_request_time = $connection["first_request_date"] ? get_date($connection["first_request_date"]) : get_date();

    if (get_date()- $first_request_time<= 60){
        if ($connection["request_count"] > 10){
            return array(false, "too_much_requests");
        }else{
            $req= "UPDATE connection_history SET request_count= request_count+1 WHERE user_id = ?";
            $values = array($id_user);
            execute($req, $values);
        }
    }else{
        $req= "UPDATE connection_history SET request_count=1, first_request_date=? WHERE user_id = ?";
        $values = array(get_date_string(), $id_user);
        execute($req, $values);
    }

    $good_credentials = validate_user($email, $password);

    $first_fail_date = $connection["first_fail_date"] ? get_date($connection["first_fail_date"]) : get_date();
    if (get_date() - $first_fail_date <= 60){
        if ($connection["fail_count"] > 5){
            return array(false, "too_much_fail");
        }else{
            $req= "UPDATE connection_history SET fail_count= ? WHERE user_id = ?";
            $values = array(($good_credentials) ? "0":$connection["fail_count"]+1, $id_user);
            execute($req, $values);
            return $good_credentials;
        }
    }else{
        if ($good_credentials){
            $req = "UPDATE connection_history SET fail_count=0 WHERE user_id = ?";
            $values = array($id_user);
        }else{
            $req = "UPDATE connection_history SET fail_count=1, first_fail_date=? WHERE user_id = ?";
            $values = array(get_date_string(), $id_user);
        }
        execute($req, $values);
        return $good_credentials;
    }

}

/**
* verify if the user has the right to access the API with an anti-DDOS like system
* 
* @return true if the user can continue, false if he should be disconnected.
* v1 : use cookies to verify the user. 
* todo : add verification with IP
**/
function verify_user_ddos(){

    $max_requests = 100;
    $period = 60;//1 minute

    if (! isset($_SESSION["user_id"])){
        if (! isset($_SESSION["user_login"])){
            $_SESSION["user_login"] = array("last_connection"=>time(),
                                            "number_tries"=>1);
            return true;
        }else{
            if (time() - $_SESSION["user_login"]["last_connection"] > $period){
                $_SESSION["user_login"]["last_connection"] = time();
                $_SESSION["user_login"]["number_tries"] = 0;
            }
            $_SESSION["user_login"]["number_tries"]++; 

            return  $_SESSION["user_login"]["number_tries"] < $max_requests;
        }
    }else{
        $id_user = $_SESSION["user_id"];
        $i = 3;
        do{
            $req = "SELECT * FROM connection_history WHERE user_id = ?";
            $values = array($id_user);

            $connection = execute($req, $values)->fetch();

            if (! $connection){
                $req= "INSERT INTO connection_history(user_id) VALUES (?)";
                $values = array($id_user);
                execute($req, $values);
            }
        }while((! $connection) && $i-- > 0);

        if ($i==0){
            return true;
        }

        $first_request_time = $connection["first_request_date"] ? get_date($connection["first_request_date"]) : get_date();

        if (get_date()- $first_request_time< $period){
            $req= "UPDATE connection_history SET request_count= request_count+1 WHERE user_id = ?";
            $values = array($id_user);
            execute($req, $values);

            return $connection["request_count"] < $max_requests;
        }else{
            $req= "UPDATE connection_history SET request_count=1, first_request_date=? WHERE user_id = ?";
            $values = array(get_date_string(), $id_user);
            execute($req, $values);
            return true;
        }
    }
}

/**
* update the last_connection_date of the user 
* @param id the user's id
**/
function update_last_connection_user($id){
    $req = "UPDATE users SET last_connection_date = ? WHERE id = ?";
    $values = array(get_date_string(), $id);
    execute($req, $values);
}

/**
* "delete" the user from the database by setting his deletion_date.
* @param id the user's id
**/
function delete_user($id){
    $req = "UPDATE users SET deletion_date=NOW() WHERE id=:id;";
    $values = array(":id"=>$id);
    execute($req, $values);
}

/*------------------------------------------------------------ PROJECTS ------------------------------------------------------------------*/

/**
* add a project
* @param name the name of the project
* @param creator_id a user's id
* @param ticket_prefix the prefix of the ticket (4 char)
*
* @return the id of the project
**/
function add_project($name, $creator_id, $ticket_prefix){
    $req = "INSERT INTO projects(name, creator_id, ticket_prefix) VALUES (?, ?, ?)  RETURNING id;";
    $values = array($name, $creator_id, $ticket_prefix);

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

/**
* delete a project*
* @param id the project's id
**/
function delete_project($id){
    $req = "DELETE FROM projects WHERE id = ?";
    $values = array($id);
    execute($req,$values);
}

/**
* get a project's data
* @param id the project's id
*
* add a "creator" and "editor" field, which contains an user's data or null if the id is not defined
* @return the project's data + creator and editor fields
**/
function get_project($id){
    $req = "SELECT * FROM projects WHERE id = ?";
    $values = array($id);

    $sth = execute($req,$values);
    $res = $sth->fetch(PDO::FETCH_ASSOC);

    if ($res){
        $res["creator"] = get_user($res["creator_id"]);
        if ($res["editor_id"]){
            $res["editor"] = get_user($res["editor_id"]);
        }else{
            $res["editor"] = null;
        }
    }
    return $res;
}

/**
* check if the project exists
* @param $id the project's id
*
* @return boolean
**/
function project_exists($id){
    if (get_project($id)){
        return true;
    }
    return false;
}

/*------------------------------------------------------------ PROJECTS & USER -----------------------------------------------------------*/

/**
* add a link between a user and a project
* @param id_user the user's id
* @param id_project the project's id
* @param level the level of access to the project. levels go from 0 (no access) to 4 (administrator)
*
* die if a link already exists
**/
function add_link_user_project($id_user, $id_project, $level){
    //check if the user does'nt already possess a link to this project
    if (get_link_user_project($id_user, $id_project) !== false){
        die("Link already existing");
    }

    $req = "INSERT INTO link_user_project VALUES (?,?,?);";
    $values = array($id_user, $id_project, $level);
    execute($req, $values);
}

/**
* get the link between the user and the project. returns false if the link doesn't exist
* @param id_user the user's id
* @param id_project the project's id
*
* @return the full link or false if the link doesn't exist
**/
function get_link_user_project($id_user, $id_project){
    $req = "SELECT * FROM link_user_project WHERE user_id = ? AND project_id = ?";
    $values = array($id_user, $id_project);

    $sth = execute($req, $values);
    if ($sth->rowCount() > 0){
        return $sth->fetch(PDO::FETCH_ASSOC);
    }
    return false;
}

/**
* get all the projects for a user
* @param id_user
* @param limit = 20 the max number of result to return
* @param offset = 0 the offset to apply to the results
*
* @return a list of projects
*
* add a access_level field to each project corresponding to the user access : 1 = read only --> 5 = creator
**/
function get_projects_for_user($id_user, $limit=20, $offset=0){
    $req = "SELECT * FROM projects WHERE id IN (SELECT project_id FROM link_user_project WHERE user_id = :user_id) OR creator_id = :user_id ORDER BY name OFFSET :offset LIMIT :limit;";
    $values = array(":user_id"=>$id_user,
                    ":offset"=>(int) $offset,
                    ":limit"=>(int) $limit);

    $sth = execute($req, $values);

    $res = $sth->fetchall(PDO::FETCH_ASSOC);

    if ($res){
        for ($i = 0; $i < count($res); $i++){
            $res[$i]["creator"] = get_user($res[$i]["creator_id"]);
            if ($res[$i]["editor_id"]){
                $res[$i]["editor"] = get_user($res[$i]["editor_id"]);
            }else{
                $res[$i]["editor"] = null;
            }
            $res[$i]["access_level"] = access_level($id_user, $res[$i]["id"]);
        }

    } 
    return $res;
}


/**
* return the number of project the user has access to
* @param id_user
*
* @return integer
**/
function get_number_projects_for_user($id_user){
    $req = "SELECT COUNT(*) AS c FROM projects WHERE id IN (SELECT project_id FROM link_user_project WHERE user_id = :user_id) OR creator_id = :user_id;";
    $values = array(":user_id"=>$id_user);

    $sth = execute($req, $values);

    $res = $sth->fetch(PDO::FETCH_ASSOC);
    
    return (int) $res["c"];
}

/**
* get all the users for the project
* @param limit = 20 the max number of result to return
* @param offset = 0 the offset to apply to the results
*
* add a access_level to each user : 1 : read only -> 5 : creator
* @return a list of users
*/
function get_users_for_project($id_project, $limit=20, $offset=0){
    $req = "SELECT * FROM users WHERE id IN (SELECT user_id FROM link_user_project WHERE project_id = :project_id UNION".
        " SELECT creator_id FROM projects WHERE id=:project_id)";
    $values = array(":project_id"=>$id_project);

    $sth = execute($req, $values);

    $result = $sth->fetchall(PDO::FETCH_ASSOC);
    for ($i = 0; $i < count($result); $i++){
        unset($result[$i]["password"]);
        $result[$i]["access_level"] = access_level((int) $result[$i]["id"], $id_project);
    }

    /*reorder the list by access level*/
    usort($result, function($a, $b){
        if ($a["access_level"] === $b["access_level"]){
            return 0;
        }
        return $a["access_level"] > $b["access_level"] ? -1 : +1; // ordre décroissant
    });

    //offset and limit have to be manually used to preserve the order
    $output = array();
    for ($i = $offset; $i < min($offset + $limit, count($result)); $i++){
        $output[] = $result[$i];
    }

    return $output;
}

/**
* get the number of users on a project
* @param id_project
* @return integer
**/
function get_number_users_for_project($id_project){
    $req = "SELECT COUNT(*) AS c FROM users WHERE id IN (SELECT user_id FROM link_user_project WHERE project_id = :project_id UNION".
        " SELECT creator_id FROM projects WHERE id=:project_id)";
    $values = array(":project_id"=>$id_project);

    $sth = execute($req, $values);

    $result = $sth->fetch(PDO::FETCH_ASSOC)["c"];

    return (int) $result;
}

/**
* modify the level on the (id_user, id_project) link
* @param $id_user
* @param $id_project
* @param $level
**/
function edit_link_user_project($id_user, $id_project, $level){
    //check if the user does'nt already possess a link to this project
    if (get_link_user_project($id_user, $id_project) === false){
        die("Lien non existant");
    }

    $req = "UPDATE link_user_project SET user_access = ? WHERE user_id = ? AND project_id = ?;";
    $values = array($level, $id_user, $id_project);
    execute($req, $values);
}

/**
* delete the link between an user and a project
* @param id_user
* @param id_project
**/
function delete_link_user_project($id_user, $id_project){
    $req = "DELETE FROM link_user_project WHERE user_id = ? AND project_id = ?;";
    $values = array($id_user, $id_project);
    execute($req, $values);
}

/**
* check if the user is an admin on the project
* @param $id_user
* @param $id_project
*
* @return a boolean indicating if his access is >= 4 (admins & creator)
**/
function is_admin($id_user, $id_project){
    return access_level($id_user, $id_project) >= 4;
}

/**
* get the access the user has on the project
* @param $id_user
* @param $id_project
*
* @return the access that a user has for a project.
* 0 : no access
* 1 : read only
* 2 : comment + read
* 3 : add ticket + comment + read
* 4 : admin
* 5 : creator
**/
function access_level($id_user, $id_project){
    /*creator*/
    $req = "SELECT creator_id FROM projects WHERE id=?";
    $args = array($id_project);

    $id = (int) execute($req, $args)->fetch()["creator_id"];

    if ($id == $id_user){
        return 5;
    }

    /*other ranks*/
    $req = "SELECT COALESCE(user_access,0) FROM link_user_project WHERE project_id = ? AND user_id = ?";
    $values = array($id_project,$id_user);
    $req = execute($req, $values);

    $access_level = $req->fetchColumn();
    return (int) $access_level;
}

/*----------------------------------------------------------------- TICKETS --------------------------------------------------------------*/


/**
* add a ticket
* @param $title string
* @param $project_id integer
* @param $creator_id integer
* @param $manager_id integer
* @param $priority integer
* @param $description string
* @param $due_date string
* @param $state integer
* @param $type integer
*
* @return the ticket's id
**/
function add_ticket($title, $project_id, $creator_id, $manager_id ,$priority, $description, $due_date , $state, $type){

    $simple_id = get_number_tickets_for_project($project_id);//starts at 0

    $values = array(
        ":simple_id" => $simple_id,
        ":name" => $title,
        ":project_id" => $project_id,
        ":creator_id" => $creator_id,
        ":manager_id"=>$manager_id,
        ":priority" => $priority,
        ":description" => $description,
        ":due_date" => $due_date,
        ":state" => $state,
        ":type"=> $type
    );

    $a = array();
    $b = array();

    foreach($values as $key=>$v){
        if ($v !== null){
            $a[] = substr($key, 1);
            $b[] = $key;
            $c[$key] = $v;
        }
    }

    $req = "INSERT INTO tickets(".join(", ", $a).") VALUES (".join(", ",$b).") RETURNING id";

    /*if ($manager_id){
        $values[":manager_id"] = $manager_id;
        $req = "INSERT INTO tickets(simple_id, name, project_id, creator_id, manager_id, priority, description, due_date) VALUES (:simple_id, :name, :project_id, :creator_id, :manager_id, :priority, :description, :due_date) RETURNING id;";
    }else{
        $req = "INSERT INTO tickets(simple_id, name, project_id, creator_id, priority, description, due_date) VALUES (:simple_id, :name, :project_id, :creator_id, :priority, :description, :due_date) RETURNING id;";
    }
*/
    //return $req;
    $sth = execute($req, $c);
    return $sth->fetch()["id"];
}

/**
* return the tickets
* @param $id
*
* @return the ticket's data with added fields :
* add a creator field
* add a manager field
* add a project field
**/
function get_ticket($id){
    global $db;

    $req = "SELECT * FROM tickets WHERE id = ".(int) $id." LIMIT 1;";
    $res = $db->query($req)->fetch(PDO::FETCH_ASSOC);
    if (count($res)){
        $res["creator"] = get_user($res["creator_id"]);
        if ($res["manager_id"]){
            $res["manager"] = get_user($res["manager_id"]);
        }else{
            $res["manager"] = null;
        }

        $project = get_project($res["project_id"]);
        $res["project"] = $project;
        $res["ticket_prefix"] = $project["ticket_prefix"];
        return $res;
    }

    return false;
}

/**
* get a ticket by his simple_id
* @param $id_project
* @param $id_simple
*
* @return the ticket's data with added fields :
* add a creator field
* add a manager field
* add a project field
**/
function get_ticket_simple($id_project, $id_simple){
    $id_project = (int) $id_project;
    $id_simple = (int) $id_simple;
    $req = "SELECT * FROM tickets WHERE project_id=? AND simple_id=? ;";
    $values = array($id_project, $id_simple);

    $sth = execute($req, $values);

    $res = $sth->fetch(PDO::FETCH_ASSOC);
    if ($res){
        $res["creator"] = get_user($res["creator_id"]);
        $res["manager"] = get_user($res["manager_id"]);

        $project = get_project($res["project_id"]);
        $res["project"] = $project;
        $res["ticket_prefix"] = $project["ticket_prefix"];
        return $res;
    }
    return false;
}

/**
* return the tickets associated with the project
* @param $id_project
* @param $limit = 20 the maximum nummber of items to fetch
* @param $offset = 0 the offset to applu
*
* @return a list of tickets, with added fileds to each ticket
* add a creator field
* add a manager field
**/
function get_tickets_for_project($id_project, $limit=20, $offset=0){

    $req = "SELECT * FROM tickets WHERE project_id = :project_id ORDER BY simple_id ASC OFFSET :offset LIMIT :limit;";
    $values = array(
        ":project_id"=>$id_project,
        ":offset"=>$offset,
        ":limit"=>$limit
    );

    $res = execute($req, $values)->fetchall(PDO::FETCH_ASSOC);

    $project = get_project($id_project);
    for ($i = 0; $i < count($res); $i++){
        $res[$i]["creator"] = get_user($res[$i]["creator_id"]);
        if ($res[$i]["manager_id"]){
            $res[$i]["manager"] = get_user($res[$i]["manager_id"]);
        }

        //$res[$i]["project"] = $project;
        $res[$i]["ticket_prefix"] = $project["ticket_prefix"];
    }

    return $res;
}

/**
* return the number of tickets a project possess
* @param $id_project
*
* @return integer the number of tickets
**/
function get_number_tickets_for_project($id_project){
    $req = "SELECT COUNT(*) AS c FROM tickets WHERE project_id = :project_id;";
    $values = array(
        ":project_id"=>$id_project
    );

    $res = execute($req, $values)->fetch(PDO::FETCH_ASSOC);

    return (int) $res["c"];
}


/**
* return all the tickets the user has access to
* @param $id_user
* @param $limit = 20 the maximum nummber of items to fetch
* @param $offset = 0 the offset to apply
*
* @return a list of tickets, with added fields
* add a creator field
* add a manager field
* add a project field
**/
function get_tickets_for_user($id_user, $limit=20, $offset=0){
    $req = "SELECT * FROM tickets WHERE project_id IN (SELECT project_id FROM link_user_project WHERE user_id = :user_id AND user_access > 0 UNION SELECT id FROM projects WHERE creator_id = :user_id) ORDER BY project_id, simple_id OFFSET :offset LIMIT :limit;";
    $values = array(":user_id"=>$id_user,
                    ":offset"=>$offset,
                    ":limit"=>$limit);

    $output = execute($req, $values)->fetchall(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($output); $i++){
        $project = get_project($output[$i]["project_id"]);
        $output[$i]["project"] = $project;
        $output[$i]["ticket_prefix"] = $project["ticket_prefix"];
        if ($output[$i]["manager_id"]){
            $output[$i]["manager"] = get_user($output[$i]["manager_id"]);
        }else{
            $output[$i]["manager"] = null;
        }
        $output[$i]["creator"] = get_user($output[$i]["creator_id"]);
    }
    return $output;
}

/**
* return the number of tickets a user has access to
* @param $id_user
*
* @return integer the number of tickets
**/
function get_number_tickets_for_user($id_user){
    $req = "SELECT COUNT(*) AS c FROM tickets WHERE project_id IN (SELECT project_id FROM link_user_project WHERE user_id = :user_id AND user_access > 0 UNION SELECT id FROM projects WHERE creator_id = :user_id);";
    $values = array(":user_id"=>$id_user);

    $output = execute($req, $values)->fetch(PDO::FETCH_ASSOC)["c"];

    return (int) $output;
}

/**
* delete a ticket from the database
* @param $id
**/
function delete_ticket($id){
    global $db;
    $req = "DELETE FROM tickets WHERE id = ".(int) $id;
    $db->exec($req);
}

/**
* get the rights of the user on the ticket
* @param $user_id
* @param $ticket_id
*
* @return an integer indicating the user's rights on the ticket
*0 : no access
*1 : read only
*2 : comment
*3 : manager (edit)
*4 : creator (edit++)
*5 : admin (edit+++)
*
* return false in case of error
**/
function rights_user_ticket($user_id, $ticket_id){
    $ticket = get_ticket($ticket_id);
    if ($ticket === false){
        return false;
    }

    if (access_level($user_id, (int) $ticket["project_id"]) == 0){
        return 0;
    }

    if (is_admin($user_id, (int) $ticket["project_id"])){
        return 5;
    }

    if ((int) $ticket["creator_id"] == $user_id){
        return 4;
    }

    if ((int) $ticket["manager_id"] == $user_id){
        return 3;
    }

    return access_level($user_id, (int) $ticket["project_id"]);
}

/*----------------------------------------------------------------- COMMENTS -------------------------------------------------------------*/

/**
* add a comment
* @param $ticket_id
* @param $creator_id
* @param $comment
*
* @return the id of the created comment
**/
function add_comment($ticket_id, $creator_id, $comment){
    $req = "INSERT INTO comments(ticket_id, creator_id, comment) VALUES (?,?,?) RETURNING id;";
    $values = array($ticket_id, $creator_id, $comment);

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

/**
* edit a comment
* @param $id the comment's id
* @param $comment the edited comment
**/
function edit_comment($id, $comment){
    $req = "UPDATE comments SET comment = ?, edition_date = ? WHERE id = ?";
    $values = array($comment,get_date_string(), $id);

    execute($req, $values);
}

/**
* delete a comment
* @param $id the comment's id
**/
function delete_comment($id){
    global $db;
    $req = "DELETE FROM comments WHERE id = ".(int) $id;
    $db->exec($req);
}

/**
* return the comment
* @param $id
*
* @return the comment with added fields
* add a creator field
* add a ticket field
**/
function get_comment($id){
    $req = "SELECT * FROM comments WHERE id = ? LIMIT 1;";
    $values= array($id);

    $sth = execute($req, $values);
    $res = $sth->fetch(PDO::FETCH_ASSOC);

    if ($res){
        $res["creator"] = get_user($res["creator_id"]);
        $res["ticket"] = get_ticket($res["ticket_id"]);
    }

    return $res;
}

/**
* return the comments for a ticket
* @param $id_ticket
* @param $limit = 20 the maximum nummber of items to fetch
* @param $offset = 0 the offset to applu
*
* @return a list of comments with added field to each comment : 
* add a creator field
**/
function get_comments_for_ticket($id_ticket, $limit=20, $offset=0){
    $req = "SELECT * FROM comments WHERE ticket_id = :ticket_id ORDER BY creation_date OFFSET :offset LIMIT :limit;";
    $values= array(":ticket_id" =>$id_ticket,
                   ":offset"=>$offset,
                   ":limit"=>$limit);

    $sth = execute($req, $values);
    $res = $sth->fetchall(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($res); $i++){
        $res[$i]["creator"] = get_user($res[$i]["creator_id"]);
    }

    return $res;
}

/**
* return the number of comments on the ticket
* @param $id_ticket
*
* @return integer the number of comments
**/
function get_number_comments_ticket($id_ticket){
    $req = "SELECT COUNT(*) AS c FROM comments WHERE ticket_id = :ticket_id;";
    $values= array(":ticket_id" =>$id_ticket);
    $sth = execute($req, $values);
    $res = $sth->fetch(PDO::FETCH_ASSOC);

    return (int) $res["c"];
}

/**
* get the user's rigts over a comment
* @param $user_id
* @param $comment_id
*
* @return the rights of the user on the comment
* 0 : no access
* 1 : read
* 2 : edit
**/
function rights_user_comment($user_id, $comment_id){
    $comment = get_comment($comment_id);

    $ticket = get_ticket((int) $comment["ticket_id"]);

    if ($ticket === false){
        return 0;
    }

    $ticket_id = $comment["ticket_id"];

    if (rights_user_ticket($user_id, (int) $ticket_id) == 0){
        return 0;
    }

    if ($ticket["creator_id"] == $user_id || is_admin($user_id, (int) $ticket["project_id"])){
        return 2;
    }

    return 1;
}

/*----------------------------------------------------------------- CATEGORIES -----------------------------------------------------------*/

/**
* N.B : these functions are not yet used by the api. they belong to a higher version of the application we haven't reached yet
*
*
**/


/**
* add a category
* @param $project_id
* @param $name_category
*
* @return tha category's id
**/
function add_category($project_id, $name_category){
    $req = "INSERT INTO categories(project_id, name) VALUES (?,?) RETURNING id;";
    $values = array($project_id, $name_category);

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

/**
* edit a category
* @param $id
* @param $project_id
* @param $name_category
**/
function edit_category($id, $project_id, $name_category){
    $req = "UPDATE categories SET project_id = ?, name = ? WHERE id = ?";
    $values = array($project_id, $name_category, $id);

    $sth = execute($req, $values);
}

/**
* delete a category
* @param $id the category's id
**/
function delete_category($id){
    global $db;
    $req = "DELETE FROM categories WHERE id = ".(int) $id;
    $db->exec($req);
}

/**
* get the category's data
* @param $id the category's id
*
* @return the category's data
**/
function get_category($id){
    global $db;
    $req = "SELECT * FROM categories WHERE id = ".(int) $id;
    $sth = $db->query($req);
    return $sth->fetch(PDO::FETCH_ASSOC);
}

/**
* get all categories associated with a project
* @param $id_project
*
* @return a list of categories
**/
function get_categories_for_project($id_project){
    global $db;
    $req = "SELECT * FROM categories WHERE project_id = ".(int) $id_project;
    $sth = $db->query($req);
    return $sth->fetchall(PDO::FETCH_ASSOC);
}

/*--------------------------------------------------------- CATEGORIES & TICKETS ---------------------------------------------------------*/

/**
* add the link if the ticket and the category are on the same project
* @param $id_ticket
* @param $id_category
*
* @return boolean indicating if the link has been created
**/
function add_link_ticket_category($id_ticket, $id_category){
    $ticket = get_ticket($id_ticket);
    $category = get_category($id_category);

    if ((int) $ticket["project_id"] == (int) $category["project_id"] && (int) $category["project_id"] != 0){        
        $req = "INSERT INTO link_ticket_category VALUES (?,?)";
        $values = array($id_ticket, $id_category);

        execute($req, $values);//postgreSQL check itself for duplicatas
        return true;
    }

    return false;
}

/**
* delete the link between a ticket and a category
* @param $id_ticket
* @param $id_category
**/
function delete_link_ticket_category($id_ticket, $id_category){
    $req = "DELETE FROM link_ticket_category WHERE ticket_id = ? AND category_id = ?;";
    $values = array($id_ticket, $id_category);

    execute($req, $values);
}

/**
* get the categories a ticket has
* @param $id_ticket
*
* @return a list of categories
**/
function get_categories_for_ticket($id_ticket){
    $req = "SELECT * FROM categories WHERE id IN (SELECT category_id FROM link_ticket_category WHERE ticket_id = ?);";
    $values = array($id_ticket);

    $sth = execute($req, $values);

    return $sth->fetchall(PDO::FETCH_ASSOC);
}

/**
* get all tickets of a designed category
* @param $id_category
*
* @return a list of tickets
**/
function get_tickets_for_category($id_category){
    $req = "SELECT * FROM tickets WHERE id IN (SELECT ticket_id FROM link_ticket_category WHERE category_id = ?);";
    $values = array($id_category);

    $sth = execute($req, $values);

    return $sth->fetchall(PDO::FETCH_ASSOC);
}

//php file : do not put "? >" at the end to the risk of having a whitespace included 