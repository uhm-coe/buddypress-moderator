(function($){
    
    jQuery(document).ready(function ($) {
        $('.moderator_table').tablesorter();
        
        //control view of table rows
        $(document).on('click', '.view', function(){
            toggle_rows($(this).val());
        });
        
        //control if someone selects an action
        $(document).on('click', '.actions a', function(event){
            event.preventDefault();
            //get action
            var action = $(this).attr('class');
            var tr = $(this).closest('tr');
            var td = $(this).closest('td');
            var id = td.data('id');
            //send to server to update results
            jQuery.ajax({
                type: 'post',
                dataType: 'json',
                url: admin_object.ajax_url,
                data: {
                        action: 'moderate_action',
                        id: id,
                        directive: action,
                        nonce: admin_object.nonce,
                },
                success: function(response) {
                    tr.removeClass(tr.attr('class'));
                    tr.addClass(action);
                    tr.toggleClass('hide');
                    
                    //mark content as moderated change buttons appropriately
                    switch(action){
                        case 'archived_released':
                            td.html('<a class="archived_moderated">Moderate</a> <a class="moderate">Return to Pending</a>');
                            break;
                        case 'archived_moderated':
                            td.html('<a class="archived_released">Release</a> <a class="moderate">Return to Pending</a>');
                            break;
                        case 'moderate':
                            td.html('<a class="archived_released">Release</a> <a class="archived_moderated">Moderate</a>');
                            break;
                    }
                },
                error: function() {
                        
                        alert("Was unable to take requested action.");
                },
            });
            
        })
    });
    
    function hide_all_rows(){
        $('.moderator_table tbody tr').each(function(){
            if (! $(this).hasClass('hide')) {
                $(this).addClass('hide');
            }
        });
    }
    
    function toggle_rows( row_class ) {
        hide_all_rows();
        
        $('tr.'+row_class).each( function(){
            $(this).removeClass('hide');
        });
    }
})(jQuery)
