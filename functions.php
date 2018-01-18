<?php

/*start the session*/
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once "db_connect.php";

/**
* function returning current time - use only this one to avoid using two references
* @param str the date in string : "2017-12-19 14:38:45.12345"
**/
function get_date($str = null){
    if ($str){
        return strtotime($str);
    }
    return time();
}

/**
* use this function instead of the pgsql::now() to avoid time differences
**/
function get_date_string(){
    return date("Y-m-d h:i:s", get_date());
}

/*-------------------------------------------------------------- USERS -------------------------------------------------------------------*/

/** hash the password
* V1 : stupid hash + permasalt
**/
function hash_passwd($pswd){//TODO add salt with timestamp
    $salt_pre = "ticket'hack";
    $salt_post = "145698235";

    return hash('sha256',$salt_pre.$pswd.$salt_post);
}

/*test a mail to check if it exists within the database
* @return boolean
**/
function user_test_mail($mail){
    $req = "SELECT COUNT(email) FROM users WHERE email=?;";
    $values = array($mail);

    $result = execute($req, $values);

    return $result->fetchColumn() === 1;
}

/** add a user
* the function user_test_mail is called prior to this function
* return the id of the row inserted
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

/** get the data from the user
* the password will NOT be transmitted here
*
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

/** validate the user's password**/
function validate_user($email, $password){
    global $db;

    $req = "SELECT count(email) AS c FROM users WHERE email=? AND password=? AND deletion_date IS NULL";
    $values = array($email, hash_passwd($password));

    $result = execute($req, $values);
    return $result->fetch()["c"] >= 1; 
}

/*test this function*/
/*validate the user's password with a fail count, and deny access if the fail count is too high even if the combination is good*/
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

function update_last_connection_user($id){
    $req = "UPDATE users SET last_connection_date = ? WHERE id = ?";
    $values = array(get_date_string(), $id);
    execute($req, $values);
}

function delete_user($id){
    /*$req = "DELETE FROM users WHERE id=:id";*/
    $req = "UPDATE users SET deletion_date=NOW() WHERE id=:id;";
    $values = array(":id"=>$id);
    execute($req, $values);
}

/*------------------------------------------------------------ PROJECTS ------------------------------------------------------------------*/

/** add a project
* return the id of the row inserted
**/
function add_project($name, $creator_id, $ticket_prefix){
    $req = "INSERT INTO projects(name, creator_id, ticket_prefix) VALUES (?, ?, ?)  RETURNING id;";
    $values = array($name, $creator_id, $ticket_prefix);

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

/*delete a project*/
function delete_project($id){
    $req = "DELETE FROM projects WHERE id = ?";
    $values = array($id);
    execute($req,$values);
}

/**
* get a project
* add a "creator" field, corresponding to a user information
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

function project_exists($id){
    if (get_project($id)){
        return true;
    }
    return false;
}

/*------------------------------------------------------------ PROJECTS & USER -----------------------------------------------------------*/

function add_link_user_project($id_user, $id_project, $level){
    //check if the user does'nt already possess a link to this project
    if (get_link_user_project($id_user, $id_project) !== false){
        die("Link already existing");
    }

    $req = "INSERT INTO link_user_project VALUES (?,?,?);";
    $values = array($id_user, $id_project, $level);
    execute($req, $values);
}

/*get the link between the user and the project. returns false if the link doesn't exist*/
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
* add a access_level field to each project
**/
function get_projects_for_user($id_user, $offset=0, $limit=20){
    $req = "SELECT * FROM projects WHERE id IN (SELECT project_id FROM link_user_project WHERE user_id = :user_id) OR creator_id = :user_id OFFSET :offset LIMIT :limit;";
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
* get all the users for the project
* add a access_level to each user
*/
function get_users_for_project($id_project){
    $req = "SELECT * FROM users WHERE id IN (SELECT user_id FROM link_user_project WHERE project_id = :project_id UNION SELECT creator_id FROM projects WHERE id=:project_id);";
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

    return $result;
}

/*modify the level on the (id_user, id_project) link*/
function edit_link_user_project($id_user, $id_project, $level){
    //check if the user does'nt already possess a link to this project
    if (get_link_user_project($id_user, $id_project) === false){
        die("Lien non existant");
    }

    $req = "UPDATE link_user_project SET user_access = ? WHERE user_id = ? AND project_id = ?;";
    $values = array($level, $id_user, $id_project);
    execute($req, $values);
}

/*delete the link*/
function delete_link_user_project($id_user, $id_project){
    $req = "DELETE FROM link_user_project WHERE user_id = ? AND project_id = ?;";
    $values = array($id_user, $id_project);
    execute($req, $values);
}

function is_admin($id_user, $id_project){
    return access_level($id_user, $id_project) >= 4;
}

/**
* return the access that a user has for a project.
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

/** add a ticket
* return the id of the row inserted
**/
function add_ticket($title, $project_id, $creator_id, $manager_id ,$priority, $description, $due_date , $state, $type){

    $simple_id = count(get_tickets_for_project($project_id));

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
* add a creator field
* add a manager field
* add a project field
*
**/
function get_ticket($id){
    global $db;

    $req = "SELECT * FROM tickets WHERE id = ".(int) $id." LIMIT 1;";
    $res = $db->query($req)->fetch(PDO::FETCH_ASSOC);
    if (count($res)){
        $res["creator"] = get_user($res["creator_id"]);
        $res["manager"] = get_user($res["manager_id"]);

        $project = get_project($res["project_id"]);
        $res["project"] = $project;
        $res["ticket_prefix"] = $project["ticket_prefix"];
        return $res;
    }

    return false;
}

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
* return the tickets
* add a creator field
* add a manager field
*
**/
function get_tickets_for_project($id_project){
    global $db;

    $req = "SELECT * FROM tickets WHERE project_id = ".(int) $id_project." ORDER BY simple_id ASC";
    $res = $db->query($req)->fetchall(PDO::FETCH_ASSOC);
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

/*return all the tickets the user has access to*/
function get_tickets_for_user($id_user, $limit=20, $offset=0){
    $req = "SELECT * FROM tickets WHERE project_id IN (SELECT project_id FROM link_user_project WHERE user_id = :user_id AND user_access > 0 UNION SELECT id FROM projects WHERE creator_id = :user_id) ORDER BY project_id, simple_id OFFSET :offset LIMIT :limit;";
    $values = array(":user_id"=>$id_user,
                    ":offset"=>$offset,
                    ":limit"=>$limit);

    $output = execute($req, $values)->fetchall(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($output); $i++){
        $project = get_project($output[$i]["project_id"]);
        //$output[$i]["project"] = $project;
        $output[$i]["ticket_prefix"] = $project["ticket_prefix"];
    }

    return $output;
}

/*delete a ticket from the database (!= ticket passed to achieved) */
function delete_ticket($id){
    global $db;
    $req = "DELETE FROM tickets WHERE id = ".(int) $id;
    $db->exec($req);
}

/** return the rights of the user on the ticket
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

/*add a comment
@return the id of the created comment*/
function add_comment($ticket_id, $creator_id, $comment){
    $req = "INSERT INTO comments(ticket_id, creator_id, comment) VALUES (?,?,?) RETURNING id;";
    $values = array($ticket_id, $creator_id, $comment);

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

function edit_comment($id, $comment){
    $req = "UPDATE comments SET comment = ?, edition_date = ? WHERE id = ?";
    $values = array($comment,get_date_string(), $id);

    execute($req, $values);
}

function delete_comment($id){
    global $db;
    $req = "DELETE FROM comments WHERE id = ".(int) $id;
    $db->exec($req);
}

/**
* return the comment
* add a creator field
* add a ticket field
**/
/*todo : test*/
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
* return the comment
* add a creator field
**/
/*todo : test*/
function get_comments_for_ticket($id_ticket){
    $req = "SELECT * FROM comments WHERE ticket_id = ?";
    $values= array($id_ticket);

    $sth = execute($req, $values);
    $res = $sth->fetchall(PDO::FETCH_ASSOC);

    for ($i = 0; $i < count($res); $i++){
        $res[$i]["creator"] = get_user($res[$i]["creator_id"]);
    }

    return $res;
}

/** return the rights of the user on the ticket
*0 : no access
*1 : read
*2 : edit
* return false in case of error
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

/** add a category
* return the inserted id
**/
function add_category($project_id, $name_category){
    $req = "INSERT INTO categories(project_id, name) VALUES (?,?) RETURNING id;";
    $values = array($project_id, $name_category);

    $sth = execute($req, $values);
    return $sth->fetch()["id"];
}

function edit_category($id, $project_id, $name_category){
    $req = "UPDATE categories SET project_id = ?, name = ? WHERE id = ?";
    $values = array($project_id, $name_category, $id);

    $sth = execute($req, $values);
}

function delete_category($id){
    global $db;
    $req = "DELETE FROM categories WHERE id = ".(int) $id;
    $db->exec($req);
}

function get_category($id){
    global $db;
    $req = "SELECT * FROM categories WHERE id = ".(int) $id;
    $sth = $db->query($req);
    return $sth->fetch(PDO::FETCH_ASSOC);
}

function get_categories_for_project($id_project){
    global $db;
    $req = "SELECT * FROM categories WHERE project_id = ".(int) $id_project;
    $sth = $db->query($req);
    return $sth->fetchall(PDO::FETCH_ASSOC);
}

/*--------------------------------------------------------- CATEGORIES & TICKETS ---------------------------------------------------------*/

/** add the link if the ticket and the category are on the same project
* return true if the link exists at the end of this function
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

/** delete the link between a ticket and a category**/
function delete_link_ticket_category($id_ticket, $id_category){
    $req = "DELETE FROM link_ticket_category WHERE ticket_id = ? AND category_id = ?;";
    $values = array($id_ticket, $id_category);

    execute($req, $values);
}

/* get categories id for a ticket */
function get_categories_for_ticket($id_ticket){
    $req = "SELECT * FROM categories WHERE id IN (SELECT category_id FROM link_ticket_category WHERE ticket_id = ?);";
    $values = array($id_ticket);

    $sth = execute($req, $values);

    return $sth->fetchall(PDO::FETCH_ASSOC);
}

/*get all tickets of a designed category*/
function get_tickets_for_category($id_category){
    $req = "SELECT * FROM tickets WHERE id IN (SELECT ticket_id FROM link_ticket_category WHERE category_id = ?);";
    $values = array($id_category);

    $sth = execute($req, $values);

    return $sth->fetchall(PDO::FETCH_ASSOC);
}
?>