<?php
$output = array();

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





echo json_encode($output);
?>