## API

### General information
* **Request results**
	* Success : `{"result" : "ok", "content" : {...}}`
	* Error : `{"result" : "error", "error" : "message"}`

### Users
* **POST** `/api/login | /api/user/login`
	* *Create a new php session for the user and authentify the user*
	* params : email, password
* **GET** `/api/logout | /api/disconnect | /api/user/logout | /api/user/disconnect`
	* *Delete the current php session and unauthentify the user*
* **POST** `/api/user/new | /api/user/add`
	*  *Create a new user*
	* params : name, email, password
* **GET** `/api/user/{id} | /api/user/me`
	* *Returns the "full" information on the wanted user*
	* `{"id" : 123456, "creation_date" : 1515483816, "name" : "test", "email" : "test@test.com", "last_connection_date" : 1515483816}`
* **POST** `/api/user/{id}/edit | /api/user/me/edit`
	* *Update information on the user  (need to be current user or superadmin)* 
	* params : (name), (email), (password)
* **DELETE** `/api/user/{id}/delete | /api/user/me/delete`
	* *"Delete" the user (need to be current user)*

### Projects
* **GET** `/api/project/list | /api/user/{id}/projects | /api/user/me/projects`
	* *Returns a list of project associated to the current user* 
	* params : (number), (offset), (query), (order)
	* `{"total" : 15, "list" : [{"id" : 123456, "creation_date" : 1515483816, "name" : "testproject", "ticket_prefix" : "TEST"}, ...]}`
* **POST** `/api/project/new`
	* *Create a new project*
	* params : name, ticket_prefix
* **GET** `/api/project/{id}`
	* *Returns the "full" information on the wanted project  (need project read access)*
	* `{"id" : 123456, "creation_date" : 1515483816, "name" : "testproject", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_prefix" : "TEST"}`
* **POST** `/api/project/{id}/edit`
	* *Update information on the project  (need project admin access)* 
	* params : (name), (ticket_prefix)
* **POST** `/api/project/{id}/delete`
	* *Delete the project  (need project admin access)*
	* params : confirm
* **POST** `/api/project/{id}/adduser`
	* *Add or update a user access to a project  (need project admin access)* 
	* params : id_user, access_level
* **GET** `/api/project/{id}/users`
	* *Get all users associated to this project  (need project read access)* 
    * `[{"id" : 123456, "name" : "testuser"},...]`
* **POST** `/api/project/{id}/removeuser`
	* *Remove the user's access to the project  (need project admin access)* 
	* params : id_user
* **GET** `/api/project/{id}/tickets`
	* *Returns a list of tickets associated to the project* 
	* params : (number), (offset), (query), (order)
	* `{"total" : 15, "list" : [{"id" : 123456, "creation_date" : 1515483816, "name" : "testticket", "simple_id" : "TEST-001", "project_id" : 123456, "manager_id" : 123456, "priority" : 5, "state" : 2, "due_date" : 1515483816}, ...]}`

### Tickets
* **GET** `/api/ticket/list`
	* *Returns a list of tickets associated to the current user (at least read access)* 
	* params : (number), (offset), (query), (order)
	* `{"total" : 15, "list" : [{"id" : 123456, "creation_date" : 1515483816, "name" : "testticket", "simple_id" : "TEST-001", "project_id" : 123456, "manager_id" : 123456, "priority" : 5, "state" : 2, "due_date" : 1515483816}, ...]}`
* **POST** `/api/ticket/new | /api/project/{id}/addticket`
	* *Create a new ticket  (need project write access)*
	* params : title, (project_id if not given), (manager_id), priority, state, (due_date)
* **GET** `/api/ticket/{id} | /api/project/{id_project}/ticket/{id_simple_ticket}`
	* *Returns the "full" information on the wanted ticket if access to it  (need project read access)*
	* `{"id" : 123456, "creation_date" : 1515483816, "name" : "testticket", "creator_id" : 123456, "edition_date" : 1515483816, "simple_id" : "TEST-001", "project_id" : 123456, "manager_id" : 123456, "priority" : 5, "state" : 2, "description" : "Lorem ipsum...", "due_date" : 1515483816, "comments" : [{"id" : 123456, "creation_date" : 1515483816, "comment" : "lorem ipsum...", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_id" : 123456}, ...]}`
* **POST** `/api/ticket/{id}/edit`
	* *Update information on the ticket  (need project write access)* 
	* params : (name), (manager_id), (priority), (state), (description), (due_date)
* **DELETE** `/api/ticket/{id}/delete`
	* *Delete the ticket (need project write access)*

### Comments
* **GET** `/api/ticket/{id}/comments`
	* *Returns all the comments associated to this ticket*
	* `[{"id" : 123456, "creation_date" : 1515483816, "comment" : "lorem ipsum...", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_id" : 123456},...]`
* **POST** `/api/comment/new | /api/ticket/{id}/addcomment`
	* *Add a new comment to the selected ticket* 
	* params : (ticket_id if not given), comment
* **GET** `/api/comment/{id}`
	* *Returns the "full" information on the wanted comment  (need project read access)*
	* `{"id" : 123456, "creation_date" : 1515483816, "comment" : "lorem ipsum...", "creator_id" : 123456, "edition_date" : 1515483816, "ticket_id" : 123456}`
* **POST** `/api/comment/{id}/edit`
	* *Update information on the comment  (need project comment access)* 
	* params : (comment)
* **DELETE** `/api/comment/{id}/delete`
	* *Delete the comment (need project comment access)*
