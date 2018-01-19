//register Math functions to be used without 'Math.(...)'
'floor|random|round|abs|sqrt|PI|atan2|sin|cos|pow|max|min|hypot'.split('|').forEach(function (p) {
    window[p] = Math[p];
});


//GENERAL

//Generate an integer within range [min, max]
function randInt(min, max) {
    return floor((random() * (max + 1)) + min);
}

//Pad a number with leading 0 to the given size : 3 -> 003
function pad(num, size) {
    var s = num + "";
    while (s.length < size) s = "0" + s;
    return s;
}

//Check validity of JSON with a regex
function validJSON(text) {
    return /^[\],:{}\s]*$/.test(text.replace(/\\["\\\/bfnrtu]/g, '@').replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']').replace(/(?:^|:|,)(?:\s*\[)+/g, ''));
}

//Transform a timestamp to a readable date
function prettyDate(timestamp) {
    var date = new Date(timestamp);
    return date.toGMTString();
}

//Check if a string is numeric
function isNumeric(n) {
    return !isNaN(parseFloat(n)) && isFinite(n);
}

//Get the GMT equivalent of given date
function getGMTDate(date) {
    return new Date(date.valueOf() - date.getTimezoneOffset() * 60000);
}

//Get a formated ticket name from JSON value returned by API
function getTicketName(ticket) {
    return ticket.ticket_prefix + "-" + pad(parseInt(ticket.simple_id), 3);
}

//COOKIES

//write a cookie with the given name, value and lifetime in days
function writeCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

//read a cookie from its name
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

//read a cookie and delete it
function readAndErase(name) {
    var c = readCookie(name);
    eraseCookie(name);
    return c;
}

//delete a cookie
function eraseCookie(name) {
    writeCookie(name, "", -1);
}

//AJAX

//create an ajax request with support of current API response format
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
                console.log(data);
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

//create a get request with support of current API response format
function ajax_get(request) {
    ajax('GET', request['url'], request['data'], request['success'], request['error']);
}

//create a post request with support of current API response format
function ajax_post(request) {
    ajax('POST', request['url'], request['data'], request['success'], request['error']);
}

//create a delete request with support of current API response format
function ajax_delete(request) {
    ajax('DELETE', request['url'], request['data'], request['success'], request['error']);
}

//NOTIFICATIONS

//initialize the notifications div and read notification cookie if it exists
function initNotification(divName) {
    $(divName).append('<div id="notifications"></div>');
    //$("#notifications").width($(divName).width());

    var notf = readAndErase("notify");
    if (notf && notf.length > 0)
        notify(notf);
}

//create a notification
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

        setTimeout(function () {
            if ($("#notify-" + id).length > 0) {
                $("#notify-" + id).animate({
                    opacity: 0
                }, 1000, function () {
                    $("#notify-" + id).remove();
                });
            }
        }, 1000);
    }
}

//remove all notifications shown on screen
function clearNotification() {
    if ($("#notifications").length > 0) {
        $("#notifications").html("");
    }
}

// LOADING

//Add a loading element to given div
function addLoading(divName, size = 3) {
    $(divName).append('<div id="load-div" class="text-center"><i class="fa fa-spinner fa-spin fa-' + size + 'x fa-fw"></i><span class="sr-only">Loading...</span></div>');
}

//remove the loading element from screen
function removeLoading() {
    while ($("#load-div").length > 0) {
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

//return the hash of an input and clean it
function getHashAndClean(inputName) {
    var hash = CryptoJS.SHA256($(inputName).val()).toString();
    $(inputName).val("");
    return hash;
}

//register current modifications
var customInputInfos = {};

//add components around an input to be modified with style
function registerCustomInput(name, textarea, callback, autoscroll = true) {
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
        autoTextArea(name);
        if (autoscroll)
            $("#" + name).scroll();
    }
}

//Format a textarea to resize with content change
function autoTextArea(name) {
    $("#" + name).on("change", function () {
        $("#" + name).scroll();
    });
    $("#" + name).scroll(function () {
        console.log("yay" + randInt(0, 999));
        $("#" + name).attr("rows", 1);
        while ($("#" + name)[0].scrollHeight > $("#" + name).innerHeight()) {
            $("#" + name).attr("rows", parseInt($("#" + name).attr("rows")) + 1);
        }
    });
}

//Register current dropdown custom lists
var customDropdownValues = {};

//Create a dropdown in the given div
function initDropdown(name, ddtype, def, values = {}, readonly = false) {
    switch (ddtype) {
        case "status":
            if (readonly) {
                $("#" + name).html('<div class="dropdown-toggle-readonly" id="dropdownStatus"></div>');
            } else {
                $("#" + name).html('<button class="btn btn-default dropdown-toggle" type="button" id="dropdownStatus" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button><div id="dropdownStatusMenu" class="dropdown-menu" aria-labelledby="dropdownStatus"></div>');
                for (var status = 0; status < 4; status++) {
                    $("#dropdownStatusMenu").append('<a class="dropdown-item" href="#" onclick="updateDropdown(\'status\',' + status + ')"><i class="fa ' + status_icons[status] + ' "></i> ' + status_titles[status] + '</a>');
                }
            }
            break;
        case "type":
            if (readonly) {
                $("#" + name).html('<div class="dropdown-toggle-readonly" id="dropdownType"></div>');
            } else {
                $("#" + name).html('<button class="btn btn-default dropdown-toggle" type="button" id="dropdownType" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button><div id="dropdownTypeMenu" class="dropdown-menu" aria-labelledby="dropdownType"></div>');
                for (var type = 0; type < 3; type++) {
                    $("#dropdownTypeMenu").append('<a class="dropdown-item" href="#" onclick="updateDropdown(\'type\',' + type + ')"><span class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span> ' + type_titles[type] + '</a>');
                }
            }
            break;
        case "priority":
            if (readonly) {
                $("#" + name).html('<div class="dropdown-toggle-readonly" id="dropdownPriority"></div>');
            } else {
                $("#" + name).html('<button class="btn btn-default dropdown-toggle" type="button" id="dropdownPriority" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button><div id="dropdownPriorityMenu" class="dropdown-menu" aria-labelledby="dropdownPriority"></div>');
                for (var priority = 0; priority < 5; priority++) {
                    $("#dropdownPriorityMenu").append('<a class="dropdown-item" href="#" onclick="updateDropdown(\'priority\',' + priority + ')"><i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + '"></i> ' + priority_titles[priority] + '</a>');
                }
            }
            break;
        case "access":
            if (readonly) {
                $("#" + name).html('<div class="dropdown-toggle-readonly" id="dropdownAccess"></div>');
            } else {
                $("#" + name).html('<button class="btn btn-default dropdown-toggle" type="button" id="dropdownAccess" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button><div id="dropdownAccessMenu" class="dropdown-menu" aria-labelledby="dropdownAccess"></div>');
                for (var access = 1; access < 5; access++) {
                    $("#dropdownAccessMenu").append('<a class="dropdown-item" href="#" onclick="updateDropdown(\'access\',' + access + ')">' + access_titles[access] + '</a>');
                }
            }
            break;
        default:
            if (readonly) {
                $("#" + name).html('<div class="dropdown-toggle-readonly" id="dropdown' + ddtype + '"></div>');
            } else {
                $("#" + name).html('<button class="btn btn-default dropdown-toggle" type="button" id="dropdown' + ddtype + '" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"></button><div id="dropdown' + ddtype + 'Menu" class="dropdown-menu" aria-labelledby="dropdown' + ddtype + '"></div>');
                for (key in values) {
                    $('#dropdown' + ddtype + 'Menu').append('<a class="dropdown-item" href="#" onclick="updateDropdown(\'' + ddtype + '\',' + key + ')">' + values[key] + '</a>');
                }
            }
            customDropdownValues[ddtype] = values;
            break;
    }
    updateDropdown(ddtype, def);
}

//Update a dropdown selected information and call changeDropdown(ddtype,val) if it exists
function updateDropdown(ddtype, val) {
    switch (ddtype) {
        case "status":
            $("#dropdownStatus").html('<i class="fa ' + status_icons[val] + ' "></i> ' + status_titles[val]);
            break;
        case "type":
            $("#dropdownType").html('<span class="fa-stack ' + type_colors[val] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[val] + ' fa-stack-1x fa-inverse"></i></span> ' + type_titles[val]);
            break;
        case "priority":
            $("#dropdownPriority").html('<i class="fa fa-thermometer-' + val + ' ' + priority_colors[val] + '"></i> ' + priority_titles[val]);
            break;
        case "access":
            $("#dropdownAccess").html(access_titles[val]);
            break;
        default:
            $("#dropdown" + ddtype).html(customDropdownValues[ddtype][val]);
            break;
    }
    if (typeof changeDropdown !== 'undefined')
        changeDropdown(ddtype, val);
}

//VIEWS

//Add a project into the project list
function addProject(id, simple_id, name) {
    if ($("#projectList").length > 0) {
        var html = '<div class="project" onclick="project_click(' + id + ',\'' + simple_id + '\')">' + '<h4>' + simple_id + ' <small>' + name + '</small></h4></div>';
        $("#projectList").append(html);
    }
}

//Add a ticket into the ticket list
function addTicket(name, desc, type, priority, status, user) {
    if ($("#ticketList").length > 0) {
        if (user && user.length > 0) user = '<h5 class="text-primary">' + user + '</h5>';
        var html = '<div class="ticket" onclick="ticket_click(\'' + name + '\')">' + '<span title="' + type_titles[type] + '" class="fa-stack ' + type_colors[type] + ' type">' + '<i class="fa fa-square fa-stack-2x"></i>' + '<i class="fa ' + type_icons[type] + ' fa-stack-1x fa-inverse"></i></span>' + '<i class="fa ' + status_icons[status] + ' status" title="status : ' + status_titles[status] + '"></i><i class="fa fa-thermometer-' + priority + ' ' + priority_colors[priority] + ' priority" title="priority : ' + priority_titles[priority] + '"></i>' + user + '<h4>' + name + ' <small>' + desc + '</small></h4></div>';
        $("#ticketList").append(html);
    }
}

//Add a user into the user list
function addUser(id, user_access, name, del) {
    if ($("#userList").length > 0) {
        var html = '<div id="user-' + id + '" class="user col-lg-3 col-6" ' + (del ? 'onclick="delete_user(' + id + ')"' : 'style="cursor:default;"') + '><b>' + access_titles[user_access] + '</b> ' + name + (del ? '<i class="fa fa-times"></i>' : '') + '</div>';
        $("#userList").append(html);
    }
}
