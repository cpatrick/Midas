/*global $*/
/*global document*/
/*global json*/

var midas = midas || {};
midas.user = midas.user || {};
midas.user.login = midas.user.login || {};

midas.user.login.validateLoginForm = function () {
    $('input[name=previousuri]').val(json.global.currentUri);
    if($('#password').val() == '') {
        midas.createNotice('Password field must not be empty', 3500, 'error');
        return false;
    }
};

midas.user.login.loginResult = function (responseText) {
    'use strict';
    try {
        var resp = $.parseJSON(responseText);
        if(resp.status) {
            window.location.href = resp.redirect;
        } else {
            midas.createNotice(resp.message, 5000, 'error');
        }
    } catch(e) {
        midas.createNotice('An internal error occured, please contact your administrator',
                           5000, 'error');
    }
};

$(document).ready(function () {
    'use strict';

    $('form#loginForm').ajaxForm({
        beforeSubmit: midas.user.login.validateLoginForm,
        success: midas.user.login.loginResult
    });

    // Deal with password recovery
    $('a#forgotPasswordLink').click(function () {
        midas.loadDialog("forgotpassword", "/user/recoverpassword");
        midas.showDialog("Recover Password");
    });

    $("a.registerLink").unbind('click').click(function () {
        midas.showOrHideDynamicBar('register');
        midas.loadAjaxDynamicBar('register', '/user/register');
    });

});
