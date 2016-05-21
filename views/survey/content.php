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
    var parent_id   = (getQueryVariable('parent'))  ? getQueryVariable('parent'):0;
    var group_id    = (getQueryVariable('group'))   ? getQueryVariable(getQueryVariable):0;
    if (!profile.survey.length) {
        getitem(id,function(item) {
            item = JSON.parse(item);
            switch (item[0].type) {
                case "question":
                    $('#title').html(item[0].label);
                    getlist(id,parent_id,group_id,function(res) {
                        for (var i in res) {
                            $('#list').append('<option/>');
                            var option = $('#list option:last-child');
                            option.attr('id',res[i].id);
                            option.attr('parent_id',res[i].parent_id);
                            option.attr('group_id',res[i].group_id);
                            option.attr('group_id',res[i].value);
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
});
function getitem(id,cb) {
    $.ajax({
        url: '/survey/getitem/'+id,
        success: function(res) {
            cb(res);
        }
    });
}
function getlist(id,parent_id,group_id,cb) {
    $.ajax({
        url: '/survey/getitems',
        type: 'POST',
        data: 'id='+id+'&parent='+parent_id+'&group='+group_id,
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