jQuery(document).ready(function ($) {
    //control button clicks for flagging post
    $(document).on('click', 'a.flag_for_moderation', function(event){
        event.preventDefault();
        //get id and type
        var id = $(this).data('id');
        var type = $(this).data('type');
        var link = $(this);
        
        //send AJAX call and mark post as moderated
         jQuery.ajax({
                type: 'post',
                dataType: 'json',
                url: flag_object.ajax_url,
                data: {
                        action: 'mark_for_moderation',
                        id: id,
                        type: type,
                        nonce: flag_object.nonce,
                },
                success: function(response) {
                    //mark content as moderated
                    if( type ==='reply' ){
                        link.closest('.bbp-reply-header').next('div').find('.bbp-reply-content').html('<p class="moderated">This content has been flagged for moderation.</p>');
                    }
                },
                error: function() {
                        // console.log( 'An error occurred.  Data not saved for student.' );
                        alert("Was unable to add to moderated list.");
                },
        });
    })
});
