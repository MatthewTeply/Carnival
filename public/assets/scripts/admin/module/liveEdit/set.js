$(document).ready(function () {

    function checkEditedNodes() {
        if($('.le-edited').length == 0) {
            $('.le-save').attr('disabled', true);
        }

        else {
            $('.le-save').removeAttr('disabled');
        }
    }

    $('.le-save').click(function (e) {
        e.preventDefault();

        let notifier = new AWN();

        // Metadata
        let route = $('#le-meta-route').val();
        let url   = $('#le-meta-url-set').val();

        $('.le-edited').each(function (index) {
            let _this = $(this);

            let name    = $(this).attr('data-le-name');
            let type    = $(this).attr('data-le-type');
            let content = '';

            switch (type) {
                case undefined:
                case 'text':
                    content = $(this).html();
                    break;
                case 'img':
                    content = $(this).attr('src').split($('#le-meta-web-storage').val())[1];
                    break;
            }

            // TODO: Going to do scan instead of whatever this is
            /*
            let contentOriginalOuter = data[7]['value'];
            let contentOriginalInner = data[8]['value'];
            */

            $.ajax({
                url: url,
                method: 'POST',
                data: {
                    name,
                    route,
                    content,
                    type
                },
                success: function (response) {
                    try {
                        response = JSON.parse(response);

                        if(response.error) {
                            notifier.alert(response.error);
                        } else {
                            _this.removeClass('le-edited');
                            delete originalContentObject[_this.attr('data-le-name')];

                            checkEditedNodes();
                        }
                    } catch (e) {
                        console.error(response);
                        notifier.alert(response);
                    }
                }
            });

        });
    });

});