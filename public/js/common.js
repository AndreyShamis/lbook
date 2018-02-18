function loadResources() {
    $.ajax({
        url: "resource",
        cache: false,
        success: function (html) {
            $("#content").html(html);
        }
    });
}



function loadNewResource()
{
    document.getElementById('openForm').style.visibility = "hidden";
    $("#linkNewResource").html(document.getElementById('closeForm'));
    document.getElementById('closeForm').style.visibility = "visible";
    
    $.ajax({
        url: "resource/new",
        cache:false,
        success: function(html){
            $("#newResource").html( html);
        }
    });
}

function loadEditResource(id_resource)
{
    $.ajax({
        url: "resource/" + id_resource + "/edit",
        cache:false,
        success: function(html){
            $("#content").html( html);
        }
    });
}

function loadResourceChannels(id_resource)
{
        $.ajax({
        url: "/resourcechannel",
        method:"POST",
        cache:false,
        data:"id_resource=" + id_resource,
        success: function(html){
            $("#channels").html( html);
        }});
}

function loadNewResourceChannel(resource_id)
{   
    document.getElementById('openForm').style.visibility = "hidden";
    $("#linkContent").html(document.getElementById('closeForm'));
    document.getElementById('closeForm').style.visibility = "visible";
    
    $.ajax({
        url: "resourcechannel/new/" + resource_id,
        cache:false,
        
        success: function(html){
            $("#newResourceChannelDiv").html( html);
        }
    });
}


function loadShowResourceChannel(id_resource)
{
    $.ajax({
        url: "resourcechannel/" + id_resource,
        cache:false,
        success: function(html){
            $("#content").html( html);
        }
    });
}

function loadEditResourceChannel(id_resource_channel)
{
    $.ajax({
        url: "resourcechannel/" + id_resource_channel + "/edit",
        cache:false,
        success: function(html){
            $("#content").html( html);
        }
    });
}

function clickOnOpenForm()
{
    $('#openForm').trigger('click');
    $('#linkNewResource').find('a').trigger('click');
    alert($('#linkNewResource').find('a'));
}

function AJAX_GET(testID)
{
    $.ajax({
        url: "EXAMPLE/exam-ple.php?PARAMETER=" + testID,
        cache:false,
        success: function(html){
            $("#WHERE_TO_PLACE_RESULTS").html( html);
        }
    });
}

function AJAX_POST()
{
    $.post("EXAMPLE/exam-ple.php" ,
        $("#WHAT_TO_POST__FORM").serializeArray() ,function(data) {
        $("#WHERE_TO_PLACE_RESULTS").html(data);
    });
}

