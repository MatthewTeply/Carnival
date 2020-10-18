$(document).ready(function() {

    function makeid(length) {
        var result           = '';
        var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        for ( var i = 0; i < length; i++ ) {
           result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    function registerEditor(el) {
        switch(el.attr('data-le-type')) {
            case undefined:
            case 'text':
                el.attr('contenteditable', true);
                
                CKEDITOR.inline(el.attr('id'));

                break;
            case 'img':
                el.click(function() {
                    fm = window.open($('#le-meta-url-fm').val(), 'File manager', 'location=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes,height=600,width=1024');
                    if (window.focus) {fm.focus()}
                });
                break;
        }
    }

    // Initial scan
    // - If node does not have le-name data attribute, it gets registered
    // - TODO: Creates all CKEditor instances for each node
    $('.le-node').each(function(index) {
        let _this = $(this);

        // Node is not registered, register it
        if($(this).attr('data-le-name') === undefined) {
            let content = '';
            let contentOriginal = {};

            switch(_this.attr('data-le-type')) {
                case undefined:
                case 'text':
                    content = _this.html();

                    contentOriginal = {
                        'inner': _this[0].innerHTML,
                        'outer': _this[0].outerHTML
                    }

                    break;
                case 'img':
                    content = _this.attr('src');

                    contentOriginal = {
                        'inner': _this[0].src,
                        'outer': _this[0].outerHTML
                    }

                    break;
            }

            $.ajax({
                url: $('#le-meta-url-scan').val(),
                method: 'POST',
                data: {
                    template: $('#le-meta-template').val(),
                    route: $('#le-meta-route').val(),
                    name: 'le' + makeid(10),
                    content: content,
                    contentOriginal: contentOriginal,
                    type: _this.attr('data-le-type') ?? ''
                },
                success: function(response) {
                    response = JSON.parse(response);

                    console.log('LE Node ' + response.name + ' has been created!');

                    _this.attr('data-le-name', response.name);
                    _this.attr('id', response.name);

                    registerEditor(_this);
                }
            });    
        }

        // Node is registered
        else {
            registerEditor(_this);
        }
    });

});