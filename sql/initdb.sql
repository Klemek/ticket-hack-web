/*https://www.postgresql.org/docs/9.2/static/datatype.html*/

/* pseudo_random function https://stackoverflow.com/questions/20890129/postgresql-random-primary-key */
CREATE OR REPLACE FUNCTION pseudo_encrypt(VALUE int) returns int AS $$
	DECLARE
	l1 int;
	l2 int;
	r1 int;
	r2 int;
	i int:=0;
	BEGIN
		l1:= (VALUE >> 16) & 65535;
		r1:= VALUE & 65535;
		WHILE i < 3 LOOP
		l2 := r1;
		r2 := l1 # ((((1366 * r1 + 150889) % 714025) / 714025.0) * 32767)::int;
		l1 := l2;
		r1 := r2;
		i := i + 1;
		END LOOP;
		RETURN ((r1 << 16) + l1);
	END;
$$ LANGUAGE plpgsql strict immutable;

/* storage of the current seed */
DROP TABLE IF EXISTS random_seed;
CREATE TABLE random_seed(
seed int default cast(extract(epoch from current_timestamp) as integer)
);
INSERT INTO random_seed default VALUES;

/* main random_int() function for generating random integers */
CREATE OR REPLACE FUNCTION random_int() returns int AS $$
	DECLARE
	s int;
	BEGIN
		s := (SELECT seed FROM random_seed LIMIT 1);
		s := pseudo_encrypt(s+1);
		UPDATE random_seed SET seed = s;
		RETURN s;
	END;
$$ LANGUAGE plpgsql;

/*SELECT n, random_int() FROM generate_series(1,1000) n; => 65ms*/

/* users arent dropped but deletion_date is used to exclude deleted ones from queries */
DROP TABLE IF EXISTS users CASCADE;
CREATE TABLE users(
	id int not null default random_int(),
	creation_date timestamp not null default current_timestamp,
	deletion_date timestamp, /* if not null, user was deleted */
	active boolean not null default false, /* user has confirmed his email */
	name varchar(256) not null,
	email varchar(256) unique not null,
	password varchar(256) not null, /* sha256 */
	last_connection_date timestamp,
	primary key (id)
);

/* INSERT INTO users (name, email, password) VALUES ('name','email','password'); */

DROP TABLE IF EXISTS projects CASCADE;
CREATE TABLE projects(
	id int not null default random_int(),
	creation_date timestamp not null default current_timestamp,
	edition_date timestamp,
	name varchar(256) not null,
	creator_id int not null,
	ticket_prefix varchar(5) not null, /* used for tickets identification */
	primary key (id),
	foreign key (creator_id) references users(id)
);

/* INSERT INTO projects (name, creator_id, ticket_prefix) VALUES ('name',id,'EXA'); */

DROP TABLE IF EXISTS link_user_project;
CREATE TABLE link_user_project(
	user_id int not null,
	project_id int not null,
	user_access int not null default 0,
	primary key (user_id, project_id),
	foreign key (user_id) references users(id),
	foreign key (project_id) references projects(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS tickets CASCADE;
CREATE TABLE tickets(
	id int not null default random_int(),
	creation_date timestamp not null default current_timestamp,
	edition_date timestamp,
	simple_id varchar(32) not null, /* example EXA-002 */ 
	name varchar(512) not null,
	project_id int not null,
	creator_id int not null,
	manager_id int,
	priority int not null default 3, /* 1-lowest, 5-hightest */
	state int not null default 0, /* 0-todo, 1-doing, 2-review, 3-done*/
	description varchar(4096),
	due_date timestamp,
	primary key (id),
	foreign key (creator_id) references users(id),
	foreign key (manager_id) references users(id),
	foreign key (project_id) references projects(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS comments;
CREATE TABLE comments(
	id int not null default random_int(),
	creation_date timestamp not null default current_timestamp,
	edition_date timestamp,
	comment varchar(4096) not null,
	ticket_id int not null,
	creator_id int not null,
	primary key (id),
	foreign key (ticket_id) references tickets(id) ON DELETE CASCADE,
	foreign key (creator_id) references users(id)
);

DROP TABLE IF EXISTS categories CASCADE;
CREATE TABLE categories(
	id int not null default random_int(),
	project_id int not null,
	name varchar(256) not null,
	primary key (id),
	foreign key (project_id) references projects(id) ON DELETE CASCADE
);

DROP TABLE IF EXISTS link_ticket_category;
CREATE TABLE link_ticket_category(
	ticket_id int not null,
	category_id int not null,
	primary key (ticket_id, category_id),
	foreign key (ticket_id) references tickets(id) ON DELETE CASCADE,
	foreign key (category_id) references categories(id) ON DELETE CASCADE
);

