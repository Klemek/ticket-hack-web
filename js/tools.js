'floor|random|round|abs|sqrt|PI|atan2|sin|cos|pow|max|min|hypot'.split('|').forEach(function (p) {
    window[p] = Math[p];
});
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
//OTHER
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
//CUSTOM
var customInputInfos = {};

function registerCustomInput(name, textarea) {
    $("#form-" + name).append('<label id="' + name + 'Edit" style="display: none;" class="col-form-label"><i id="' + name + 'Confirm" class="fa fa-check"></i><i id="' + name + 'Cancel" class="fa fa-times" style="margin-left: 10px;"></i></label><label id="' + name + 'EditHint" style="display: none;" class="col-form-label"><i class="fa fa-pencil"></i></label>');
    $("#" + name).click(function () {
        customInputInfos[name] = $("#" + name).val();
        $("#" + name).removeAttr("readonly");
        $("#" + name).removeClass("form-control-plaintext");
        $("#" + name + "Edit").css("display", "block");
        $("#" + name + "EditHint").css("display", "none");
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
        customInputInfos[name] = $("#" + name).val();
        $("#" + name).blur();
    });
    $("#form-" + name).submit(function () {
        customInputInfos[name] = $("#" + name).val();
        $("#" + name).blur();
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