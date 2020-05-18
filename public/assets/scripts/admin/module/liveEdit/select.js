$(document).ready(function() {

    let originalContent;

    $(document).on('mouseover', '.le-container *', function(e) {
        e.preventDefault();

        let el = $(e.target);

        if(el.hasClass('le-node-container')) {
            el.parent().trigger('mouseover');
            return false;
        }

        $('.le-hovered').each(function(index, previousEl) {
            previousEl = $(previousEl);

            previousEl.removeClass('le-hovered');
        });


        let leName = $(this).find('.le-node-container').attr('data-le-name') ?? null;
        el.attr('data-before', leName ?? 'Unnamed')

        el.addClass('le-hovered');
    });

    $(document).on('click', '.le-container *', function(e) {
        e.preventDefault();

        let el = $(e.target);

        if(el.hasClass('le-node-container')) {
            el.parent().click();
            return false;
        }

        $('.le-selected').each(function(index, previousEl) {
            previousEl = $(previousEl);

            previousEl.removeClass('le-selected');
        });

        $('#le-set-name').val('');

        let leName = $(this).find('.le-node-container').attr('data-le-name') ?? null;

        el.removeAttr('class');
        el.removeAttr('data-before');

        $('#le-set-content').val(el[0].innerHTML);
        $('#le-set-content-original-outer').val(el[0].outerHTML);
        $('#le-set-content-original-inner').val(el[0].innerHTML);

        el.addClass('le-selected')
        el.attr('data-before', leName ?? 'Unnamed')

        originalContent = el.html();

        $('#le-set-tr').show();

        $('#le-set-name').val(leName);
        $('#le-set-name').focus();
    });

    $('#le-set-content').on('keydown keyup', function() {
        $('.le-selected').html($(this).val());
    });

    $('#le-set-name').on('keydown keyup', function() {
        $('.le-selected').attr('data-before', $(this).val() ?? 'Unnamed');
    });

    $(document).on('click', '.le-edit', function() {
        let leName = $(this).attr('data-edit-le-name');

        $('#le-' + leName).parent().click();
    });
        
});