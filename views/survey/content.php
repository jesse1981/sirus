<div id="survey" class="survey-wrapper">
    <div id="question" class="hidden">
        <h3 id="title"></h3>
        <select id="list">
            <option value="0">-- Select One --</option>
        </select>
    </div>
</div>
<script>
$(document).ready(function() {
    var id          = (getQueryVariable('id'))      ? getQueryVariable('id'):1;
    if (!profile.survey.length) {
        getitem(id,function(item) {
            item = JSON.parse(item);
            switch (item[0].type) {
                case "question":
                    $('#title').html(item[0].label);
                    getlist(id,function(res) {
                        for (var i in res) {
                            $('#list').append('<option/>');
                            var option = $('#list option:last-child');
                            option.attr('object_id',res[i].id);
                            option.attr('id',res[i].value);
                            option.text(res[i].label);
                        }
                    });
                    break;
                case "answer":

                    break;
            }
            
        });
    }
    else {
        alert('You have already completed this survey, do you want to record a new pain?');
    }
    
    // ********************
    // ** EVENTS
    // ********************
    $('#list').change(function() {
        var obj     = $(this).children(':selected');
        var id      = obj.attr('object_id');
        var value   = obj.attr('id');
        // put code in to store selection to local storage!
        $.ajax({
            url: '/survey/submit/'+id,
            success: function() {
                // redirect
                window.location = '/survey?id='+value;
            }
        });
    });
});
function getitem(id,cb) {
    $.ajax({
        url: '/survey/getitem/'+id,
        success: function(res) {
            cb(res);
        }
    });
}
function getlist(id,cb) {
    $.ajax({
        url: '/survey/getitems',
        type: 'POST',
        data: 'id='+id,
        dataType: 'json',
        success: function(res) {
            cb(res);
        },
        statusCode: {
            404: function() {
                alert( "page not found" );
            }
        },
        error: function(jqXHR,textStatus,errorThrown) {
            alert ("ERROR ("+textStatus+") "+errorThrown);
        }
    });
}
</script>