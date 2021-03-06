// MIDAS Server. Copyright Kitware SAS. Licensed under the Apache License 2.0.

var midas = midas || {};
midas.sizequota = midas.sizequota || {};
midas.sizequota.folder = midas.sizequota.folder || {};
midas.sizequota.constant = {
    MIDAS_USE_DEFAULT_QUOTA: "0",
    MIDAS_USE_SPECIFIC_QUOTA: "1"
};

midas.sizequota.folder.validateConfig = function (formData, jqForm, options) {}

midas.sizequota.folder.successConfig = function (responseText, statusText, xhr, form) {
    try {
        var jsonResponse = jQuery.parseJSON(responseText);
    }
    catch (e) {
        midas.createNotice("An error occured. Please check the logs.", 4000, 'error');
        return false;
    }
    if (jsonResponse == null) {
        midas.createNotice('Error', 4000, 'error');
        return;
    }
    if (jsonResponse[0]) {
        location.reload();
    }
    else {
        midas.createNotice(jsonResponse[1], 4000, 'error');
    }
}

midas.sizequota.folder.radioButtonChanged = function () {
    var selected = $('input[name="usedefault"]:checked');

    if (selected.val() == midas.sizequota.constant.MIDAS_USE_DEFAULT_QUOTA) {
        $('input#quota').attr('disabled', 'disabled');
    }
    else {
        $('input#quota').removeAttr('disabled');
    }
}

$(document).ready(function () {
    $('#configForm').ajaxForm({
        beforeSubmit: midas.sizequota.folder.validateConfig,
        success: midas.sizequota.folder.successConfig
    });

    $('input[name="usedefault"]').change(midas.sizequota.folder.radioButtonChanged);
    midas.sizequota.folder.radioButtonChanged();

    var content = $('#quotaValue').html();
    if (content != '' && content != 0) {
        var quota = parseInt($('#quotaValue').html());
        var used = parseInt($('#usedSpaceValue').html());

        if (used <= quota) {
            var free = quota - used;
            var hUsed = $('#hUsedSpaceValue').html();
            var hFree = $('#hFreeSpaceValue').html();
            var data = [
                ['Used space (' + hUsed + ')', used],
                ['Free space (' + hFree + ')', free]
            ];
            $('#quotaChart').show();
            $.jqplot('quotaChart', [data], {
                seriesDefaults: {
                    renderer: $.jqplot.PieRenderer,
                    rendererOptions: {
                        showDataLabels: true
                    }
                },
                legend: {
                    show: true,
                    location: 'e'
                }
            });
        }
    }
});
