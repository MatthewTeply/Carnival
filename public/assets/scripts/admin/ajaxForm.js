$(document).ready(function() {
    const webroot      = $('#meta-webroot').val();
    const appName      = $('#meta-appName').val();
    const appIsDefault = $('#meta-appIsDefault').val();

    let notifier = new AWN();

    $('body').on('submit', '.lampion-ajax-form', function(e) {
        e.preventDefault();

        var formData = new FormData($(this)[0]);

        $.ajax({
            type: 'POST',
            url: $(this).attr('action'),
            data: formData,
            processData: false,
            contentType: false,
            error: function(jqXHR, textStatus, errorMessage) {
                notifier.alert(errorMessage);
            },
            success: function(response) {
                let successMsg = 'Form submitted successfuly!';

                try {
                    response = JSON.parse(response);

                    if(response.href) {
                        let pageChangeEvent = new CustomEvent('carnival-page-change', {
                            detail: {
                                href: response.href
                            }
                        });

                        window.dispatchEvent(pageChangeEvent);
                    }

                    if(response.msg) {
                        successMsg = response.msg;
                    }
                    
                    notifier.success(successMsg);
                }

                catch(e) {
                    console.log(response);
                    notifier.alert('Failed submitting form!');
                }
            } 
        });
    });

});