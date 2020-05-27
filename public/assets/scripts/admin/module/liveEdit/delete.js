$(document).ready(function() {

    let notifier = new AWN();

    $(document).on('click', '.le-delete', function(e) {
        e.preventDefault();

        if(!confirm('Are you sure?')) {
            return false;
        }

        let name     = $(this).attr('data-delete-le-name');
        let url      = $(this).attr('data-delete-le-url');
        let route    = $(this).attr('data-delete-le-route');
        let template = $(this).attr('data-delete-le-template');

        $.ajax({
            url: url,
            method: 'POST',
            data: {
                name,
                route,
                template
            },
            success: function(response) {
                try {
                    response = JSON.parse(response);

                    window.leNodesList.nodes = response.nodes;

                    $('#le-' + name).replaceWith(response.original);
                }

                catch(e) {
                    console.error(response);
                    notifier.alert(response);
                }
            }
        });
    });

});