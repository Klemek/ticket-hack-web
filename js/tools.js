'floor|random|round|abs|sqrt|PI|atan2|sin|cos|pow|max|min|hypot'.split('|').forEach(function (p) {
    window[p] = Math[p];
});


//GENERAL

function randInt(min, max) {
    return floor((random() * (max + 1)) + min);
}

function randString(list) {
    return list[randInt(0, list.length - 1)];
}

function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}

function validJSON(text) {
    return /^[\],:{}\s]*$/.test(text.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, ''));
}

function prettyDate(timestamp) {
    var date = new Date(timestamp);
    return date.toGMTString();
}

//COOKIES

function writeCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

function readCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}

function readAndErase(name) {
    var c = readCookie(name);
    eraseCookie(name);
    return c;
}

function eraseCookie(name) {
    writeCookie(name, "", -1);
}

//AJAX

function ajax(method, url, data, callbacksuccess, callbackerror) {
    $.ajax({
        url: url,
        method: method,
        data: data,
        success: function (data) {
            if (typeof data !== "object") {
                console.log("invalid JSON : " + data);
                notify("<b>Error</b> internal error", "danger");
                return;
            }

            if (data.result == "ok") {
                //console.log(data);
                if (callbacksuccess)
                    callbacksuccess(data.content);
            } else {
                notify("<strong>Error</strong> " + data.message, "danger");
                if (callbackerror)
                    callbackerror();
            }

        },
        error: function (result) {
            if (result.status == 0) {
                notify("<b>Error</b> internal error", "danger");
                console.log("unreachable url : " + url);
            } else {
                console.log("error " + result.status + " : " + result.responseText);
                if (!result.responseJSON || !result.responseJSON.error) {
                    notify("<b>Error</b> internal error", "danger");
                } else {
                    notify("<strong>Error</strong> " + result.responseJSON.error, "danger");

                }
            }
            if (callbackerror)
                callbackerror(result.status, result.responseJSON);
        }
    });
}

function ajax_get(request) {
    ajax('GET', request['url'], request['data'], request['success'], request['error']);
}

function ajax_post(request) {
    ajax('POST', request['url'], request['data'], request['success'], request['error']);
}

function ajax_delete(request) {
    ajax('DELETE', request['url'], request['data'], request['success'], request['error']);
}

//NOTIFICATIONS

function initNotification(divName) {
    $(divName).append('<div id="notifications"></div>');
    //$("#notifications").width($(divName).width());

    var notf = readAndErase("notify");
    if (notf && notf.length > 0)
        notify(notf);
}

function notify(msg, type) {
    if ($("#notifications").length > 0) {

        if (!type) {
            var spl = msg.split("-", 2);
            type = spl[0];
            msg = spl[1];
        }

        var id = randInt(0, 9999999);

        $("#notifications").append('<div id="notify-' + id + '" class="alert alert-' + type + ' alert-dismissable"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' + msg + '</div>');

        $("#notify-" + id).css("opacity", 0);
        $("#notify-" + id).animate({
            opacity: 1
        }, 500, function () {
            $("#notify-" + id).css("opacity", "default");
        });
    }
}

function clearNotification() {
    if ($("#notifications").length > 0) {
        $("#notifications").html("");
    }
}

// LOADING

function addLoading(divName) {
    $(divName).append('<div id="load-div" class="text-center"><i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span></div>');
}

function removeLoading() {
    if ($("#load-div").length > 0) {
        $("#load-div").remove();
    }
}

//CONSTANTS

var type_titles = {
        0: "bug",
        1: "improvement",
        2: "check"
    },
    type_colors = {
        0: "text-danger",
        1: "text-success",
        2: "text-primary"
    },
    type_icons = {
        0: "fa-bug",
        1: "fa-arrow-up",
        2: "fa-check"
    },
    priority_titles = {
        0: "lowest",
        1: "low",
        2: "medium",
        3: "high",
        4: "highest"
    },
    priority_colors = {
        0: "text-success",
        1: "text-success",
        2: "text-warning",
        3: "text-warning",
        4: "text-danger"
    },
    status_titles = {
        0: "open",
        1: "working",
        2: "review",
        3: "closed"
    },
    status_icons = {
        0: "fa-certificate",
        1: "fa-cogs",
        2: "fa-eye",
        3: "fa-check-circle"
    },
    access_titles = {
        1: "Reader",
        2: "Commenter",
        3: "Editor",
        4: "Admin",
        5: "Creator"
    };


//INPUTS

function getHashAndClean(inputName) {
    var hash = CryptoJS.SHA256($(inputName).val()).toString();
    $(inputName).val("");
    return hash;
}

var customInputInfos = {};

function registerCustomInput(name, textarea, callback) {
    $("#form-" + name).append('<label id="' + name + 'Edit" style="display: none;" class="col-form-label"><i id="' + name + 'Confirm" class="fa fa-check"></i><i id="' + name + 'Cancel" class="fa fa-times" style="margin-left: 10px;"></i></label><label id="' + name + 'EditHint" style="display: none;" class="col-form-label"><i class="fa fa-pencil"></i></label>');
    $("#" + name).click(function () {
        if ($("#" + name).attr("readonly")) {
            customInputInfos[name] = $("#" + name).val();
            $("#" + name).removeAttr("readonly");
            $("#" + name).removeClass("form-control-plaintext");
            $("#" + name + "Edit").css("display", "block");
            $("#" + name + "EditHint").css("display", "none");
        }
    });
    $("#" + name).hover(function () {
        if ($("#" + name).attr("readonly")) $("#" + name + "EditHint").css("display", "block");
    });
    $("#" + name).mouseleave(function () {
        $("#" + name + "EditHint").css("display", "none");
    });
    $("#" + name).blur(function () {
        if ($("#" + name).val() == customInputInfos[name]) {
            $("#" + name).attr("readonly", true);
            $("#" + name).addClass("form-control-plaintext");
            $("#" + name + "Edit").css("display", "none");
            if (textarea) $("#" + name).scroll();
        }
    });
    $("#" + name + "Cancel").click(function () {
        $("#" + name).val(customInputInfos[name]);
        $("#" + name).blur();
    });
    $("#" + name + "Confirm").click(function () {
        if ($("#" + name).val().length > 0) {
            $("#" + name).blur();
            if ($("#" + name).val() !== customInputInfos[name]) {
                customInputInfos[name] = $("#" + name).val();
                if (callback) callback(customInputInfos[name]);
            }
        }
    });
    $("#form-" + name).submit(function () {
        if ($("#" + name).val().length > 0) {
            if ($("#" + name).val() !== customInputInfos[name]) {
                customInputInfos[name] = $("#" + name).val();
                if (callback) callback(customInputInfos[name]);
            }
            $("#" + name).blur();
        }
        return false;
    });
    if (textarea) {
        $("#" + name).on("change", function () {
            $("#" + name).scroll();
        });
        $("#" + name).scroll(function () {
            $("#" + name).attr("rows", 1);
            while ($("#" + name)[0].scrollHeight > $("#" + name).innerHeight()) {
                $("#" + name).attr("rows", parseInt($("#" + name).attr("rows")) + 1);
            }
        });
        $("#" + name).scroll();
    }
}

//VIEWS

function addProject(id, simple_id, name) {
    if ($("#projectList").length > 0) {
        var html = '<div class="project" onclick="project_click(' + id + ',\'' + simple_id + '\')">' + '<h4>' + simple_id + ' <small>' + name + '</small></h4></div>';
        $("#projectList").append(html);
    }
}

function addTicket(name, desc, type, priority, status, user) {
    if ($("#ticketList").length > 0) {
        if (user.length > 0) user = '<h5 class="text-primary">' + user + '</h5>';
        var html = '<div class="ticket" onclick="ticket_click(\'' + name + '\')">' + '<span title="' + type_titles[type] + '" class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span>' + '<i class="fa ' + status_icons[status] + ' status" title="status : ' + status_titles[status] + '"></i><i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + ' priority" title="priority : ' + priority_titles[priority] + '"></i>' + user + '<h4>' + name + ' <small>' + desc + '</small></h4></div>';
        $("#ticketList").append(html);
    }
}

function addUser(id, user_access, name, del) {
    if ($("#userList").length > 0) {
        var html = '<div id="user-' + id + '" class="user col-md-3 col-sm-6" ' + (del ? 'onclick="delete_user(' + id + ')"' : 'style="cursor:default;"') + '><b>' + access_titles[user_access] + '</b> ' + name + '<i class="fa fa-times"></i></div>';
        $("#userList").append(html);
    }
}

//DESIGN FAKE VIEWS

var fakeProjectNames = ['TEST', 'RAND', 'EX', 'ABC', 'SAMP'],
    fakeProjectDesc = ['Sample project', 'Example project', 'Some project', 'Rule the world'],
    fakeTicketDesc = [
    'randomly generated ticket', 'a random ticket', 'some ticket', 'omg a ticket', 'a nice ticket', 'you should open this one'
],
    fakeUserNames = [
    'John ROBERT', 'Joseph DAVID', 'Donald CHARLES', 'Michael WILLIAMS'
];

function addFakeTicket(project) {
    if (!project)
        project = randString(fakeProjectNames)

    var name = project + "-" + pad(randInt(1, 999), 3),
        desc = randString(fakeTicketDesc),
        type = randInt(0, 2),
        priority = randInt(0, 4),
        status = randInt(0, 3),
        user = randString(fakeUserNames);

    if (randInt(0, 2) == 0)
        user = "";

    addTicket(name, desc, type, priority, status, user);
}

function addFakeProject() {
    addProject(0, randString(fakeProjectNames), randString(fakeProjectDesc));
}
