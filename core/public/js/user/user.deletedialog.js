// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.user = midas.user || {};
midas.user.deletedialog = {};

/**
 * Toggles the Delete button based on the state of the agreement checkbox
 * in order to make the operation safer
 */
midas.user.deletedialog.agreeCheckboxChanged = function () {
    if ($(this).attr('checked') == 'checked') {
        $('#deleteDialogDeleteButton').removeAttr('disabled');
    }
    else {
        $('#deleteDialogDeleteButton').attr('disabled', 'disabled');
    }
}

/**
 * When the user confirms deletion request, this will get called before ajax submission
 */
midas.user.deletedialog.confirm = function () {
    $('#deleteDialogDeleteButton').attr('disabled', 'disabled');
    $('#deleteDialogCancelButton').attr('disabled', 'disabled');
    $('#deleteDialogAgreeCheckbox').attr('disabled', 'disabled');
    $('img#deleteDialogLoadingGif').show();
    // TODO add please wait message?
}

/**
 * Called when our ajax request to delete the user returns
 */
midas.user.deletedialog.success = function (responseText, statusText, xhr, form) {
    $('div.MainDialog').dialog('close');
    $('#deleteDialogCancelButton').removeAttr('disabled');
    $('#deleteDialogAgreeCheckbox').removeAttr('disabled');
    $('#deleteDialogAgreeCheckbox').removeAttr('checked');
    $('input#declineApplyRecursive').removeAttr('disabled');
    $('img#deleteDialogLoadingGif').hide();
    var jsonResponse = $.parseJSON(responseText);

    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    midas.createNotice(jsonResponse[1], 4000);
    window.location.replace(json.global.webroot + '/user/index');
}

$(document).ready(function () {
    $('#deleteDialogAgreeCheckbox').change(midas.user.deletedialog.agreeCheckboxChanged);
    $('#deleteDialogCancelButton').click(function () {
        $('div.MainDialog').dialog('close');
    });

    $('#deleteDialogForm').ajaxForm({
        beforeSubmit: midas.user.deletedialog.confirm,
        success: midas.user.deletedialog.success
    });
});
