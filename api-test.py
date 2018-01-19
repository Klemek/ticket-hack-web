from urllib.parse import urlencode
from urllib.request import Request, urlopen
from urllib.error import HTTPError
from random import randint
import hashlib
import ssl
import json
import sys

def request(req_type, url, params):
    global cookie
    request = Request(
        server + url, urlencode(params).encode(), method=req_type)
    if cookie is not None:
        request.add_header('cookie', cookie)
    try:
        response = urlopen(request, context=context)
        if cookie is None:
            cookie = response.headers.get('Set-Cookie')
        data = response.read().decode()
        return json.loads(data)
    except HTTPError as err:
        return {"status": err.code}


def POST(url, params):
    return request("POST", url, params)


def GET(url):
    return request("GET", url, {})


def DELETE(url):
    return request("DELETE", url, {})


def sha256(text):
    sha256 = hashlib.sha256()
    sha256.update(text.encode())
    return sha256.hexdigest()


def testJSON(ref, obj):
    errors = []
    warns = []
    for key in ref.keys():
        if key not in obj:
            errors += ["key '{}' not found".format(key)]
        elif ref[key] != -1:
            if type(ref[key]) != type(obj[key]):
                errors += ["at '{}' expected type {} but got type {}".format(
                    key, type(ref[key]), type(obj[key]))]
            elif type(ref[key]) == type(dict()):
                errors2, warns2 = testJSON(ref[key], obj[key])
                errors += errors2
                warns += warns2
            elif type(ref[key]) == type(list()):
                if len(ref[key]) != len(obj[key]):
                    errors += ["at '{}' array length expected {} but got {}".format(
                        key, len(ref[key]), len(obj[key]))]
                for i in range(min(len(ref[key]), len(obj[key]))):
                    errors2, warns2 = testJSON(ref[key][i], obj[key][i])
                    errors += errors2
                    warns += warns2
            elif ref[key] != obj[key]:
                errors += ["at '{}' expected {} but got {}".format(
                    key, ref[key], obj[key])]
        elif obj[key] is None:
            errors += [
                "at '{}' expected not null value but was deceived".format(key)]
    for key in obj.keys():
        if key not in ref:
            #warns += ["key '" + key + "' not found in reference"]
            pass
    return errors, warns


def testRequest(req_type, name, url, params, expected):
    result = request(req_type, url, params)
    errors, warns = testJSON(expected, result)
    if len(errors) == 0:
        print("OK :    {:6} {:25} {} <= {}".format(
            req_type, url, result["status"], name))
        for warn in warns:
            print("\tWarning : " + warn)
        return result
    else:
        print("ERROR : {:6} {:25} {} <= {}".format(
            req_type, url, result["status"], name), file=sys.stderr)
        #print("   Expected :\t" + str(expected), file=sys.stderr)
        print("   Result :\t" + str(result), file=sys.stderr)
        for error in errors:
            print("\tError : " + error, file=sys.stderr)
        return False


def testPOST(name, url, params, expected):
    return testRequest('POST', name, url, params, expected)


def testGET(name, url, expected):
    return testRequest('GET', name, url, {}, expected)


def testDELETE(name, url, expected):
    return testRequest('DELETE', name, url, {}, expected)

def executeTests():
    global server, cookie, context
    context = ssl._create_unverified_context()
    print("Ticket'Hack API test")
    server = "https://kalioz.fr"

    print("EXECUTING TESTS ON "+server)

    print("RESULT \t{:6} {:^25} CODE    TEST EXECUTED".format("METHOD", "URL"))
    print("{:=<75}".format(""))

    tests = []

    for glob in globals():
        if callable(globals()[glob]) and glob.startswith("test_"):
            tests += [glob]
    for test in tests:    
        print("{:+^75}".format(test))
        cookie = None
        globals()[test]()

def test_all():
    uniqueid = randint(0, sys.maxsize)
    email = "test-email-" + str(uniqueid) + "@test.fr"
    email2 = "test-email-" + str(randint(0, sys.maxsize)) + "@test.fr"
    name = "test-name-" + str(uniqueid)
    password = "test-password-" + str(uniqueid)

    user_id = None
    project_id = None
    user_id2 = None

    #-------------------- USER RELATED FUNCTIONS --------------------

    testGET("project list no session", "/api/project/list",
            {'status': 401})

    res = testPOST("normal register", "/api/user/new",
                   {"email": email,
                    "password": sha256(password),
                    "name": name},
                   {'status': 200, 'result': 'ok',
                       'content': {'user_id': -1}})

    if res:
        user_id = res["content"]["user_id"]

    # 2nd user
    res = testPOST("normal register - 2nd user", "/api/user/new",
                   {"email": email2,
                    "password": sha256(password),
                    "name": name},
                   {'status': 200, 'result': 'ok',
                       'content': {'user_id': -1}})

    if res:
        user_id2 = res["content"]["user_id"]


    testPOST("register with same mail", "/api/user/new",
             {"email": email,
              "password": sha256(password),
              "name": name},
             {'status': 405})

    testPOST("login normal", "/api/user/connect",
             {"email": email,
              "password": sha256(password)},
             {'status': 200, 'result': 'ok',
              'content': {'user_id': user_id}})

    testGET("information about current user", "/api/user/me",
            {'status': 200, 'result': 'ok',
             'content': {"id": user_id,
                         "creation_date": -1,
                         "name": name,
                         "email": email,
                         "last_connection_date": -1,
                         "active": -1,
                         "deletion_date": None}})
    
    testGET("information about user by mail", "/api/user/bymail?mail="+email,
            {'status': 200, 'result': 'ok',
             'content': {"id": user_id,
                         "creation_date": -1,
                         "name": name,
                         "email": email,
                         "last_connection_date": -1,
                         "active": -1,
                         "deletion_date": None}})

    testGET("information user with id", "/api/user/" + str(user_id),
            {'status': 200, 'result': 'ok',
             'content': {"id": user_id,
                         "creation_date": -1,
                         "name": name,
                         "email": email,
                         "last_connection_date": -1,
                         "active": -1,
                         "deletion_date": None}})

    name += str(randint(0, 10))
    password += str(randint(0, 10))

    testPOST("change user information", "/api/user/me/edit",
             {"name": name,
              "password": sha256(password)},
             {'status': 200, 'result': 'ok',
                 'content': {"id": user_id,
                             "creation_date": -1,
                             "name": name,
                             "email": email,
                             "last_connection_date": -1,
                             "active": -1,
                             "deletion_date": None}})

    testGET("check information change", "/api/user/me",
            {'status': 200, 'result': 'ok',
             'content': {"id": user_id,
                         "creation_date": -1,
                         "name": name,
                         "email": email,
                         "last_connection_date": -1,
                         "active": -1,
                         "deletion_date": None}})

    testGET("logout", "/api/logout",
            {'status': 200, 'result': 'ok', 'content': {'disconnected': True}})

    testGET("project list no session", "/api/project/list",
            {'status': 401})

    testPOST("login normal", "/api/user/connect",
             {"email": email,
              "password": sha256(password)},
             {'status': 200, 'result': 'ok',
              'content': {'user_id': user_id}})

    #---------------------------- Project related tests -----------------------
    testPOST("login normal", "/api/user/connect",
             {"email": email,
              "password": sha256(password)},
             {'status': 200, 'result': 'ok',
              'content': {'user_id': user_id}})

    # logged in tests
    testGET("project list empty", "/api/project/list",
            {'status': 200, 'result': 'ok',
             'content': {'total': 0, 'list': []}})

    res = testPOST("create new project", "/api/project/new",
                   {"name": "test_project",
                    "ticket_prefix": "TEST"},
                   {'status': 200, 'result': 'ok',
                       'content': {'project_id': -1}})

    if res:
        project_id = res['content']['project_id']

    testGET("get project information", "/api/project/" + str(project_id),
            {'status': 200, 'result': 'ok',
             'content': {}})
        
    testGET("project list one result", "/api/project/list",
            {'status': 200, 'result': 'ok',
             'content': {'total': 1, 'list': [
                     {
        "id": project_id,
        "creation_date": -1,
        "edition_date": None,
        "name": "test_project",
        "editor_id": None,
        "creator_id": -1,
        "ticket_prefix": "TEST"
      }
                     ]}})

    testGET("get existing project while connected", "/api/project/"+str(project_id),
            {
      "status": 200,
      "result": "ok",
      "content": {
        "id": project_id,
        "creation_date": -1,
        "edition_date": None,
        "name": "test_project",
        "editor_id": None,
        "creator_id": -1,
        "ticket_prefix": "TEST",
        "user_access":-1
      }
    })
                    
    testGET("get user project ", "/api/user/"+str(user_id)+"/projects",
            {'status': 200, 'result': 'ok',
             'content': {'total': 1, 'list': [
                     {
        "id": project_id,
        "creation_date": -1,
        "edition_date": None,
        "name": "test_project",
        "editor_id": None,
        "creator_id": -1,
        "ticket_prefix": "TEST"
      }
    ]}})

    testPOST("edit project", "/api/project/"+str(project_id)+"/edit",
             {"name":"Name Edited myman"},
             {'status': 200, 'result': 'ok',
             'content': {
        "id": project_id,
        "creation_date": -1,
        "edition_date": -1,
        "name": "Name Edited myman",
        "editor_id": -1,
        "creator_id": -1,
        "ticket_prefix": "TEST"
      }
    })
                    
    testGET("Users with access to the project", "/api/project/"+str(project_id)+"/users",
            {
      "status": 200,
      "result": "ok",
      "content": {
              "total":1,
              "list":[
        {
          "id": user_id,
          "creation_date": -1,
          "deletion_date": None,
          "active": -1,
          "name": -1,
          "email": -1,
          "last_connection_date": -1
        }
      ]
                }
    })
                    
    testPOST("add user to project - good clearance", "/api/project/"+str(project_id)+"/adduser",
             {"user_id":user_id2,
              "access_level":4},
             {
      "status": 200,
      "result": "ok",
      "content": {
        "link_user_project": {
          "user_id": user_id2,
          "project_id": project_id,
          "user_access": 4
        }
      }
    })

    testGET("Users with access to the project - x2", "/api/project/"+str(project_id)+"/users",
            {
      "status": 200,
      "result": "ok",
      "content": {
              "total":2,
              "list":[
        {
          "id": user_id,
          "creation_date": -1,
          "deletion_date": None,
          "active": -1,
          "name": -1,
          "email": -1,
          "last_connection_date": -1,
          "access_level":5
        },
         {
          "id": user_id2,
          "creation_date": -1,
          "deletion_date": None,
          "active": -1,
          "name": -1,
          "email": -1,
          "last_connection_date": None,
          "access_level":4
        }
      ]}
    })

    testPOST("remove user from project - good clearance", "/api/project/"+str(project_id)+"/removeuser",
             {"user_id":user_id2},
             {
      "status": 200,
      "result": "ok",
      "content": {
        "delete":True
      }
    })

    testGET("Users with access to the project - after remove", "/api/project/"+str(project_id)+"/users",
            {
      "status": 200,
      "result": "ok",
      "content": {
              "total":1,
              "list":[
        {
          "id": user_id,
          "creation_date": -1,
          "deletion_date": None,
          "active": -1,
          "name": -1,
          "email": -1,
          "last_connection_date": -1,
          "access_level":5
        }
      ]
              }
    })

    testPOST("add ticket to project", "/api/project/"+str(project_id)+"/addticket",
         {"name":"New Ticket",
          "priority":4,
          "description":"sum ticket",
          "due_date":"2018-05-15 17:45:52",
          "state":1,
          "type":4},
         {
      "status": 200,
      "result": "ok",
      "content": {
        "id_ticket": -1
      }
    })

    testPOST("add ticket to project - due date non obligatoire", "/api/project/"+str(project_id)+"/addticket",
             {"name":"New Ticket 2",
              "priority":2,
              "description":"sum ticket 2",
              "state":0,
              "type":0},
             {
      "status": 200,
      "result": "ok",
      "content": {
        "id_ticket": -1
      }
    })
    
    testGET("Tickets on the project", "/api/project/"+str(project_id)+"/tickets",
            {
      "status": 200,
      "result": "ok",
      "content": {
              "total":2,
              "list":[
        {
      "id": -1,
      "creation_date": -1,
      "edition_date": None,
      "due_date": -1,
      "simple_id": "0",
      "name": -1,
      "project_id": -1,
      "editor_id": None,
      "creator_id": -1,
      "manager_id": None,
      "type": 4,
      "priority": -1,
      "state": 1,
      "description": -1
    },
                {
      "id": -1,
      "creation_date": -1,
      "edition_date": None,
      "due_date": None,
      "simple_id": "1",
      "name": -1,
      "project_id": -1,
      "editor_id": None,
      "creator_id": -1,
      "manager_id": None,
      "type": -1,
      "priority": -1,
      "state": -1,
      "description": "sum ticket 2"
    }
      ]}
    })
    
    res = testGET("Tickets on the project - simple id", "/api/project/"+str(project_id)+"/ticket/001",
            {
      "status": 200,
      "result": "ok",
      "content": 
        {
      "id": -1,
      "creation_date": -1,
      "edition_date": None,
      "due_date": None,
      "simple_id": -1,
      "name": -1,
      "project_id": -1,
      "project":-1,
      "editor_id": None,
      "creator_id": -1,
      "manager_id": None,
      "type": -1,
      "priority": -1,
      "state": -1,
      "description": -1
    }})
    
    if res:
        ticket_id = res["content"]["id"]
    else:
        return
    
    testGET("Tickets for the user", "/api/ticket/list",
            {
      "status": 200,
      "result": "ok",
      "content": 
        {"total":2,
         "list":[{
      "id": -1,
      "creation_date": -1,
      "edition_date": None,
      "due_date": -1,
      "simple_id": "0",
      "name": -1,
      "project_id": -1,
      "editor_id": None,
      "creator_id": -1,
      "manager_id": None,
      "type": 4,
      "priority": -1,
      "state": 1,
      "description": -1
    },{
      "id": -1,
      "creation_date": -1,
      "edition_date": None,
      "due_date": None,
      "simple_id": -1,
      "name": -1,
      "project_id": -1,
      "editor_id": None,
      "creator_id": -1,
      "manager_id": None,
      "type": -1,
      "priority": -1,
      "state": -1,
      "description": -1
    }
        ]}})
        
    testGET("get ticket", "/api/ticket/"+str(ticket_id),
            {
      "status": 200,
      "result": "ok",
      "content": 
        {
      "id": -1,
      "creation_date": -1,
      "edition_date": None,
      "due_date": None,
      "simple_id": -1,
      "name": -1,
      "project_id": -1,
      "project":-1,
      "editor_id": None,
      "creator_id": -1,
      "creator":-1,
      "manager_id": None,
      "manager":None,
      "type": -1,
      "priority": -1,
      "state": -1,
      "description": -1
    }})
        
    testPOST("edit ticket", "/api/ticket/"+str(ticket_id)+"/edit",
             {"name":"edited",
              "priority":4,
              "description":"myman"},
             {
      "status": 200,
      "result": "ok",
      "content": {
          "id": -1,
          "creation_date": -1,
          "edition_date": -1,
          "due_date": None,
          "simple_id": -1,
          "name": "edited",
          "project_id": -1,
          "project":-1,
          "editor_id": -1,
          "creator_id": -1,
          "creator":-1,
          "manager_id": None,
          "manager":None,
          "type": -1,
          "priority": -1,
          "state": -1,
          "description": -1
    }
    })

    
    
        
    res = testPOST("add comment to ticket", "/api/ticket/"+str(ticket_id)+"/addcomment",
             {"comment":"Un tout pitit comment"},
             {
      "status": 200,
      "result": "ok",
      "content": {
        "id_comment": -1
      }
    })
    
    
    if res:    
        comment_id = res["content"]["id_comment"]
    else:
        return
    
    testGET("get tickets comment", "/api/ticket/"+str(ticket_id)+"/comments",
            {
                 "status": 200,
      "result": "ok",
      "content": {"total":1,
                  "list":[
        {
          "id": -1,
          "creation_date": -1,
          "edition_date": None,
          "comment": "Un tout pitit comment",
          "ticket_id": ticket_id,
          "creator_id": user_id
        }
  ]}
      })
    
    testPOST("edit comment","/api/comment/"+str(comment_id)+"/edit",
             {
                     "comment":"comment edited"},
                     {
                            "status": 200,
                            "result": "ok",
                            "content": 
                                    {
                                            "id": -1,
                                            "creation_date": -1,
                                            "edition_date": -1,
                                            "comment": "comment edited",
                                            "ticket_id": ticket_id,
                                            "creator_id": user_id,
                                            "creator":-1,
                                            "ticket":-1
                                            }
                                    
                            }
            )

    #---------------------------- Delete functions --------------------------
    return
    #on project
    testDELETE("delete project","/api/project/"+str(project_id)+"/delete",
               {'status': 200, 'result': 'ok',
                'content': {'delete': True}}
               )

    #on user
    testDELETE("delete user", "/api/user/me/delete",
               {'status': 200, 'result': 'ok',
                'content': {'delete': True}})

    testPOST("login user deleted", "/api/user/connect",
             {"email": email,
              "password": sha256(password)},
             {'status': 401})



executeTests()
