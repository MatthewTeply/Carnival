$(document).ready(function() {

    $('#le-set-form').submit(function(e) {
        e.preventDefault();

        let notifier = new AWN();

        let data = $(this).serializeArray();
        
        let route                = data[0]['value'];
        let url                  = data[1]['value'];
        let template             = data[2]['value'];
        let name                 = data[3]['value'];
        let content              = data[4]['value'];
        let contentOriginalOuter = data[5]['value'];
        let contentOriginalInner = data[6]['value'];

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                route,
                name,
                content,
                template,
                contentOriginalOuter,
                contentOriginalInner
            },
            success: function(response) {
                try {
                    window.leNodesList.nodes = JSON.parse(response);
                }

                catch(e) {
                    notifier.alert(response);
                }
                $('#le-set-tr').hide();
            }
        });
    });

});