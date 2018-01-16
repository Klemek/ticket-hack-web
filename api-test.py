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
            warns += ["key '" + key + "' not found in reference"]
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
        print("   Expected :\t" + str(expected), file=sys.stderr)
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


print("Ticket'Hack API test")
server = "192.168.42.10"  # input("Enter the server ip : ")
if not(server.startswith("https")):
    server = "https://" + server

print("RESULT \t{:6} {:^25} CODE    TEST EXECUTED".format("METHOD", "URL"))
print("{:=<75}".format(""))

context = ssl._create_unverified_context()
cookie = None

uniqueid = randint(0, sys.maxsize)
email = "test-email-" + str(uniqueid) + "@test.fr"
name = "test-name-" + str(uniqueid)
password = "test-password-" + str(uniqueid)

user_id = None
project_id = None

testGET("project list no session", "/api/project/list",
        {'status': 401})

res = testPOST("normal register", "/api/user/new",
               {"email": email,
                "password": sha256(password),
                "name": name},
               {'status': 200, 'result': 'ok',
                   'content': {'id_user': -1}})

if res:
    user_id = res["content"]["id_user"]

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

testGET("project list empty", "/api/project/list",
        {'status': 200, 'result': 'ok',
         'content': {'total': 0, 'list': []}})

res = testPOST("create new project", "/api/project/new",
               {"name": "test_project",
                "ticket_prefix": "TEST"},
               {'status': 200, 'result': 'ok',
                   'content': {'id_project': -1}})

if res:
    project_id = res['content']['id_project']

testGET("get project information", "/api/project/" + str(project_id),
        {'status': 200, 'result': 'ok',
         'content': {}})

testDELETE("delete user", "/api/user/me/delete",
           {'status': 200, 'result': 'ok',
            'content': {'delete': True}})

testPOST("login user deleted", "/api/user/connect",
         {"email": email,
          "password": sha256(password)},
         {'status': 401})
