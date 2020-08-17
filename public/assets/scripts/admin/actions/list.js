$(document).ready(function() {

    let ids = [];
    
    $('.batch-action-all').change(function() {
        let isChecked = $(this).prop('checked');

        $('.batch-action').prop('checked', isChecked);
        $('.batch-action').change();
    });

    $('.batch-action').change(function() {
        if($(this).prop('checked')) {
            ids.push($(this).attr('data-id'))
    
            let deleteHref = $('.batch-delete').attr('href').split('?ids=')[0];
    
            $('.batch-delete').attr('href', deleteHref + '?ids=' + ids.join(','));
        }

        else {
            ids.splice(ids.indexOf($(this).attr('data-id')), 1);
        }

        if(ids.length > 0) {
            $('.batch-delete-btn').removeAttr('disabled');
        }
        
        else {
            $('.batch-delete-btn').attr('disabled', '');
        }
    });

});