$(document).ready(function() {

    $('#le-set-form').submit(function(e) {
        e.preventDefault();

        let notifier = new AWN();

        let data = $(this).serializeArray();
        
        let route                = data[0]['value'];
        let url                  = data[1]['value'];
        let template             = data[2]['value'];
        let editing              = data[3]['value'];
        let name                 = data[4]['value'];
        let nameOriginal         = data[5]['value'];
        let content              = data[6]['value'];
        let contentOriginalOuter = data[7]['value'];
        let contentOriginalInner = data[8]['value'];

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                route,
                name,
                content,
                template,
                contentOriginalOuter,
                contentOriginalInner,
                editing,
                nameOriginal
            },
            success: function(response) {
                try {
                    response = JSON.parse(response);

                    if(response.error) {
                        notifier.alert(response.error);
                    }

                    else {
                        $('.le-selected').html(response.content);
                        $('#le-set-form').hide();

                        window.leNodesList.nodes = response.nodes;
                    }
                }

                catch(e) {
                    console.error(response);
                    notifier.alert(response);
                }

                $('.le-selected').each(function(index, previousEl) {
                    previousEl = $(previousEl);
        
                    previousEl.removeClass('le-selected');
                });
                $('#le-set-tr').hide();
            }
        });
    });

    $('#le-set-cancel').click(function() {
        $('.le-selected').each(function(index, previousEl) {
            previousEl = $(previousEl);

            previousEl.html($('#le-set-content-original-inner').val());
            previousEl.removeClass('le-selected');
        });

        $('#le-set-form').hide();
    });

});