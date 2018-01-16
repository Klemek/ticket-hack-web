from urllib.parse import urlencode
from urllib.request import Request, urlopen
from urllib.error import HTTPError
from random import randint
import hashlib
import ssl
import json
import sys

context = ssl._create_unverified_context()
cookie = None

def request(req_type,url,params):
    global cookie
    request = Request(server+url, urlencode(params).encode(), method=req_type)
    if cookie is not None:
        request.add_header('cookie',cookie)
    try:
        response = urlopen(request, context=context)
        if cookie is None:
            cookie = response.headers.get('Set-Cookie')
        data = response.read().decode()
        return json.loads(data)
    except HTTPError as err:
        return {"code":err.code,"reason":err.reason}

def POST(url, params):
    return request("POST",url,params)

def GET(url):
    return request("GET",url,{})

def DELETE(url):
    return request("DELETE",url,{})

def sha256(text):
    sha256 = hashlib.sha256()
    sha256.update(text.encode())
    return sha256.hexdigest()

def testJSON(ref,obj):
    res = True
    for key in ref.keys():
        if key not in obj:
            print("Error : key '"+key+"' not found", file=sys.stderr)
            res = False
        elif ref[key] is not None:
            if type(ref[key]) != type(obj[key]):
                print("Error : key '"+key+"' expected type '"+str(type(ref[key]))+"' got '"+str(type(obj[key]))+"'", file=sys.stderr)
                res = False
            elif type(ref[key]) == type(dict()):
                res = res and testJSON(ref[key],obj[key])
            elif type(ref[key]) == type(list()):
                if len(ref[key]) != len(obj[key]):
                    print("Error : key '"+key+"' array length expected "+str(len(ref[key]))+" got "+str(len(obj[key]))+"", file=sys.stderr)
                    res = False
                for i in range(min(len(ref[key]),len(obj[key]))):
                        res = res and testJSON(ref[key][i],obj[key][i])
            elif ref[key] != obj[key]:
                print("Error : key '"+key+"' expected '"+str(ref[key])+"' got '"+str(obj[key])+"'", file=sys.stderr)
                res = False
    return res

def testRequest(req_type,name,url,params,expected):
    result = request(req_type,url,params)
    test = testJSON(expected,result)
    if test:
        print("OK :\t{:6} {:20} <= {}".format(req_type,url,name))
        return result
    else:
        print("Expected : "+str(expected), file=sys.stderr)
        print("Got : "+str(result), file=sys.stderr)
        print("ERROR : "+url)
        return False

def testPOST(name,url,params,expected):
    return testRequest('POST',name,url,params,expected)

def testGET(name,url,expected):
    return testRequest('GET',name,url,{},expected)

def testDELETE(name,url,expected):
    return testRequest('DELETE',name,url,{},expected)

print("Ticket'Hack API test")
server = "192.168.42.10"#input("Enter the server ip : ")
if not(server.startswith("https")):
    server = "https://" + server

print("RESULT \t{:6} {:20}    TEST".format("METHOD","URL"))
print("{:=<60}".format(""))

uniqueid = randint(0,sys.maxsize)
dbID = None

res = testPOST("normal register","/api/user/new",
         {"email":"test-email-"+str(uniqueid)+"@test.fr",
          "password":sha256("test-password"),
          "name":"test-name"},
         {'status': 200, 'result': 'ok', 'content':{'id_user':None}})

if res:
    dbID = res["content"]["id_user"]

testPOST("register with same mail","/api/user/new",
     {"email":"test-email-"+str(uniqueid)+"@test.fr",
      "password":sha256("test-password"),
      "name":"test-name"},
     {'code': 405})

testPOST("login normal","/api/user/connect",
         {"email":"test-email-"+str(uniqueid)+"@test.fr",
          "password":sha256("test-password")},
         {'status': 200, 'result': 'ok', 'content': {'user_id': dbID}})
testGET("project list empty","/api/projects/list",
        {'status': 200, 'result': 'ok', 'content': {'total': 0, 'list': []}})

testDELETE("delete user","/api/user/me/delete",
         {'status': 200, 'result': 'ok', 'content': {'delete':True}})

testPOST("login user deleted","/api/user/new",
     {"email":"test-email-"+str(uniqueid)+"@test.fr",
      "password":sha256("test-password"),
      "name":"test-name"},
     {'code': 405})
