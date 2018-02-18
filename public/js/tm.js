/**
 * Created by ashamis on 04/25/2016.
 */
function easyPieChartColor (percent) {
    return (percent < 50 ? '#D15B47' : percent < 80 ? '#87CEEB' : '#87B87F');
}

$(function () {
    $('[data-toggle="tooltip"]').tooltip()
})

/**
 * Loading available bugs in datatable in the report edit form.
 */
function showBugsTable() {
    var reportId = parseInt($("#reportId").text()) || 0;
    var url= "/report/showbugstable/" + reportId;
    if(reportId < 1){
        url = "/report/showbugstablewithoutreport"
    }
    console.log("Loading report cq into datatable on edit[showBugsTable]. Report ID is : " + reportId);
    $.ajax({
        url: url,
        cache:false,
        success: function(html){$(".reportFormBugsTable").html( html);}
    });
}

/**
 * Loading available reports in datatable in the report edit form.
 */
function showReportsTable() {
    console.log("showReportsTable");
    var reportId = parseInt($("#reportId").text()) || 0;
    // var url= "/report/showreportstable/" + reportId;
    var url= "/report/showreportstable/"+reportId;
    if(reportId < 1){
        url = "/report/showreportstablewithoutreport"
    }
    console.log(url);
    console.log("------------");
    $.ajax({
        url: url,
        cache:false,
        success: function(html){$(".reportFormAllReportsTable").html( html);}
    });
}


/**
 * This method is used in the Create and Edit report forms.
 */
function attachBugsToReport() {
    $(".bugIDcell").each(function () {
        //Check if the checkbox is checked
        if ($(this).closest('tr').find('.addBugCheckBox').is(':checked')) {
            var bugId = $(this).text();
            $("#report_ibugsCq option[value='" + bugId + "']").prop("selected", true);
            // console.log("Enable " + bugId);
        }
        else{
            var bugId = $(this).text();
            $("#report_ibugsCq option[value='" + bugId + "']").prop("selected", false);
            // console.log("Disable " + bugId);
        }
    });
}

/**
 * This method is used in the Create and Edit report forms.
 */
function attachReportsToReport() {
    $(".repIDcell").each(function () {
        //Check if the checkbox is checked
        var repId = $(this).text();
        if ($(this).closest('tr').find('.addReportCheckBox').is(':checked')) {
            $("#report_associatedReports option[value='" + repId + "']").prop("selected", true);
            console.log("Enable " + repId);
        }
        else{
            $("#report_associatedReports option[value='" + repId + "']").prop("selected", false);
            console.log("Disable " + repId);
        }
    });
}

/**
 * markdown editor related scripts - used in create / edit report
 */
function markdownEditorScript(){

        $('[data-toggle="buttons"] .btn').on('click', function(e){
            var target = $(this).find('input[type=radio]');
            var which = parseInt(target.val());
            var toolbar = $('#editor1').prev().get(0);
            if(which >= 1 && which <= 4) {
                toolbar.className = toolbar.className.replace(/wysiwyg\-style(1|2)/g , '');
                if(which == 1) $(toolbar).addClass('wysiwyg-style1');
                else if(which == 2) $(toolbar).addClass('wysiwyg-style2');
                if(which == 4) {
                    $(toolbar).find('.btn-group > .btn').addClass('btn-white btn-round');
                } else $(toolbar).find('.btn-group > .btn-white').removeClass('btn-white btn-round');
            }
        });

        //RESIZE IMAGE

        //Add Image Resize Functionality to Chrome and Safari
        //webkit browsers don't have image resize functionality when content is editable
        //so let's add something using jQuery UI resizable
        //another option would be opening a dialog for user to enter dimensions.
        if ( typeof jQuery.ui !== 'undefined' && ace.vars['webkit'] ) {

            var lastResizableImg = null;
            function destroyResizable() {
                if(lastResizableImg == null) return;
                lastResizableImg.resizable( "destroy" );
                lastResizableImg.removeData('resizable');
                lastResizableImg = null;
            }

            var enableImageResize = function() {
                $('.wysiwyg-editor')
                    .on('mousedown', function(e) {
                        var target = $(e.target);
                        if( e.target instanceof HTMLImageElement ) {
                            if( !target.data('resizable') ) {
                                target.resizable({
                                    aspectRatio: e.target.width / e.target.height,
                                });
                                target.data('resizable', true);

                                if( lastResizableImg != null ) {
                                    //disable previous resizable image
                                    lastResizableImg.resizable( "destroy" );
                                    lastResizableImg.removeData('resizable');
                                }
                                lastResizableImg = target;
                            }
                        }
                    })
                    .on('click', function(e) {
                        if( lastResizableImg != null && !(e.target instanceof HTMLImageElement) ) {
                            destroyResizable();
                        }
                    })
                    .on('keydown', function() {
                        destroyResizable();
                    });
            };
            enableImageResize();
        }
}