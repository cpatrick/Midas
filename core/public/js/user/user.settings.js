var midas = midas || {};
midas.user = midas.user || {};

midas.user.validatePasswordChange = function (formData, jqForm, options) {
    var form = jqForm[0];
    if(form.newPassword.value.length < 2) {
        midas.createNotice(jsonSettings.passwordErrorShort, 4000, 'error');
        return false;
    }
    if(form.newPassword.value.length < 2 || form.newPassword.value != form.newPasswordConfirmation.value) {
        midas.createNotice(jsonSettings.passwordErrorMatch, 4000, 'error');
        return false;
    }
}

midas.user.validatePictureChange = function (formData, jqForm, options) {
}

midas.user.validateAccountChange = function (formData, jqForm, options) {
    var form = jqForm[0];
    if(form.firstname.value.length < 1) {
        midas.createNotice(jsonSettings.accountErrorFirstname, 4000, 'error');
        return false;
    }
    if(form.lastname.value.length < 1) {
        midas.createNotice(jsonSettings.accountErrorLastname, 4000, 'error');
        return false;
    }
}

midas.user.successPasswordChange = function (responseText, statusText, xhr, form) {
    var jsonResponse = jQuery.parseJSON(responseText);
    if(jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if(jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        $('#modifyPassword input[type=password]').val('');
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

midas.user.successAccountChange = function (responseText, statusText, xhr, form) {
    var jsonResponse = jQuery.parseJSON(responseText);
    if(jsonResponse == null) {
        midas.createNotice('Error', 4000);
        return;
    }
    if(jsonResponse[0]) {
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

midas.user.successPictureChange = function (responseText, statusText, xhr, form) {
    var jsonResponse = jQuery.parseJSON(responseText);
    if(jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if(jsonResponse[0]) {
        $('img#userTopThumbnail').attr('src', jsonResponse[2]);
        midas.createNotice(jsonResponse[1], 4000);
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

$(document).ready(function() {
    $( "#tabsSettings" ).tabs();

    $( "#tabsSettings" ).css('display','block');
    $( "#tabsSettings" ).show();

    $('#modifyPassword').ajaxForm({
        beforeSubmit: midas.user.validatePasswordChange,
        success: midas.user.successPasswordChange
    });

    $('#modifyAccount').ajaxForm({
        beforeSubmit: midas.user.validateAccountChange,
        success: midas.user.successAccountChange
    });

    $('#modifyPicture').ajaxForm({
        beforeSubmit: midas.user.validatePictureChange,
        success: midas.user.successPictureChange
    });

    var jsonSettings = jQuery.parseJSON($('div.jsonSettingsContent').html());

    $('textarea#biography').attr('onkeyup', 'this.value = this.value.slice(0, 255)');
    $('textarea#biography').attr('onchange', 'this.value = this.value.slice(0, 255)');
});
