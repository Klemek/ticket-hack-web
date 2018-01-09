# Ticket-Hack-Web

## API

### General information
* **POST results**
	* Success : `{"result" : "ok", "content" : {...}}`
	* Error : `{"result" : "error", "error" : "message"}`

### Users
* **GET** `/api/user/{id}`
	* *Returns the "full" information on the wanted user*
	* `{"id" : 123456, "creation_date" : 1515483816, "name" : "test", "email" : "test@test.com", "last_connection_date" : 1515483816}`
* **POST** `/api/user/{id}`
	* *Update information on the user  (need to be current user or superadmin)* 
	* params : (name), (email), (password)
* **POST** `/api/user/{id}/delete`
	* *"Delete" the user (need to be current user or superadmin)*
	* params : confirm
* **POST** `/api/user/new`
	*  *Create a new user*
	* params : name, email, hash(pass), 
* **POST** `/api/user/connect`
	* *Try to create a new php session for the user*
	* params : email, hash(pass)
### Projects
* **GET** `/api/project/{id}`
	* *Returns the "full" information on the wanted project  (need project read access)*
	* `{"id" : 123456, "creation_date" : 1515483816, "name" : "testproject", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_prefix" : "TEST"}`
* **POST** `/api/project/{id}`
	* *Update information on the project  (need project admin access)* 
	* params : (name), (ticket_prefix)
* **POST** `/api/project/{id}/delete`
	* *Delete the project  (need project admin access)*
	* params : confirm
* **POST** `/api/project/{id}/user`
	* *Add or update a user access to a project  (need project admin access)* 
	* params : id_user, access_level
* **POST** `/api/project/{id}/removeuser`
	* *Remove the user's access to the project  (need project admin access)* 
	* params : id_user
* **GET** `/api/project/list?params`
	* *Returns a list of project associated to the current user* 
	* params : (number), (offset), (query)
	* `[{"id" : 123456, "creation_date" : 1515483816, "name" : "testproject", "ticket_prefix" : "TEST"}, ...]`
* **POST** `/api/project/new`
	* *Create a new project*
	* params : name, ticket_prefix
* **GET** `/api/project/{id}/ticketlist?params`
	* *Returns a list of tickets associated to the project* 
	* params : (number), (offset), (query)
	* `[{"id" : 123456, "creation_date" : 1515483816, "name" : "testticket", "simple_id" : "TEST-001", "project_id" : 123456, "manager_id" : 123456, "priority" : 5, "state" : 2, "due_date" : 1515483816}, ...]`
### Tickets
* **GET** `/api/ticket/{id}`
	* *Returns the "full" information on the wanted ticket if access to it  (need project read access)*
	* `{"id" : 123456, "creation_date" : 1515483816, "name" : "testticket", "creator_id" : 123456, "edition_date" : 1515483816, "simple_id" : "TEST-001", "project_id" : 123456, "manager_id" : 123456, "priority" : 5, "state" : 2, "description" : "Lorem ipsum...", "due_date" : 1515483816, "comments" : [{"id" : 123456, "creation_date" : 1515483816, "comment" : "lorem ipsum...", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_id" : 123456}, ...]}`
* **POST** `/api/ticket/{id}`
	* *Update information on the ticket  (need project write access)* 
	* params : (name), (manager_id), (priority), (state), (description), (due_date)
* **POST** `/api/ticket/{id}/delete`
	* *Delete the ticket (need project write access)*
	* params : confirm
* **GET** `/api/ticket/list?params`
	* *Returns a list of tickets associated to the current user (at least read access)* 
	* params : (number), (offset), (query)
	* `[{"id" : 123456, "creation_date" : 1515483816, "name" : "testticket", "simple_id" : "TEST-001", "project_id" : 123456, "manager_id" : 123456, "priority" : 5, "state" : 2, "due_date" : 1515483816}, ...]`
* **POST** `/api/ticket/new`
	* *Create a new ticket  (need project write access)*
	* params : name, project_id, (manager_id), priority, state, (due_date)
### Comments
* **GET** `/api/comments/{id}`
	* *Returns the "full" information on the wanted comment  (need project read access)*
	* `{"id" : 123456, "creation_date" : 1515483816, "comment" : "lorem ipsum...", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_id" : 123456}`
* **POST** `/api/comments/{id}`
	* *Update information on the comment  (need project comment access)* 
	* params : (comment)
* **POST** `/api/comments/{id}/delete`
	* *Delete the comment (need project comment access)*
	* params : confirm