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
                
                CKEDITOR.inline(el.attr('id'), {
                    sharedSpaces: {
                        top: 'ckeditor-controls'
                    },
                    skin: 'office2013'
                });

                break;
            case 'img':
                /*
                el.cropper({
                    viewMode: 2,
                    center: true,
                    restore: false,
                    responsive: true,
                    background: false,
                    crop: function(event) {
                    }
                });
                */

                var controlsContainer             = document.createElement('div');
                var controlsChooseImageBtn        = document.createElement('button');
                var controlsClearImageBtn         = document.createElement('button');
                var controlsInterlanguageCheckBox = document.createElement('input');

                // Choose Image
                controlsChooseImageBtn.innerHTML = 'Vybrat obrázek';
                controlsChooseImageBtn.classList.add('btn');
                controlsChooseImageBtn.classList.add('btn-sm');
                controlsChooseImageBtn.classList.add('btn-primary');
                controlsChooseImageBtn.classList.add('le-image-controls-choose');
                controlsChooseImageBtn.setAttribute('data-image-id', el.attr('id'));

                // Clear Image
                controlsClearImageBtn.innerHTML = 'Resetovat obrázek';
                controlsClearImageBtn.classList.add('btn');
                controlsClearImageBtn.classList.add('btn-sm');
                controlsClearImageBtn.classList.add('btn-warning');
                controlsClearImageBtn.classList.add('le-image-controls-clear');
                controlsClearImageBtn.classList.add('le-image-controls-clear-' + el.attr('id'));
                controlsClearImageBtn.setAttribute('disabled', true);
                controlsClearImageBtn.setAttribute('data-original-src', el.attr('src'));
                controlsClearImageBtn.setAttribute('data-image-id', el.attr('id'));

                // Interlanguage
                var controlsInterlanguageContainer = document.createElement('div');

                controlsInterlanguageContainer.classList.add('le-image-controls-interlanguage-container');
                controlsInterlanguageContainer.classList.add('float-right');

                controlsInterlanguageCheckBox.setAttribute('type', 'checkbox');
                controlsInterlanguageCheckBox.setAttribute('id', 'le-image-controls-interlanguage-' + el.attr('id'));
                controlsInterlanguageCheckBox.classList.add('le-image-controls-interlanguage');

                if(el.attr('data-le-interlanguage') !== undefined) {
                    controlsInterlanguageCheckBox.setAttribute('checked', true);
                }

                var controlsInterlanguageLabel = document.createElement('label');

                controlsInterlanguageLabel.innerHTML = 'Pro všechny jazyky';
                controlsInterlanguageLabel.setAttribute('for', controlsInterlanguageCheckBox.getAttribute('id'));

                controlsInterlanguageContainer.appendChild(controlsInterlanguageCheckBox);
                controlsInterlanguageContainer.appendChild(controlsInterlanguageLabel);

                // Clear Image Event
                var originalSrc = el[0].src;

                // Container
                controlsContainer.appendChild(controlsChooseImageBtn);
                controlsContainer.appendChild(controlsClearImageBtn);
                controlsContainer.appendChild(controlsInterlanguageContainer);
                
                controlsContainer.classList.add('le-image-controls-container');

                el[0].outerHTML += controlsContainer.outerHTML;

                break;
        }

        // Choose Image Event
        $('.le-image-controls-choose').click(function() {
            fm = window.open($('#le-meta-url-fm').val(), 'File manager', 'location=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes,height=600,width=1024');
            if (window.focus) {fm.focus()}
        });

        // Clear Image Event
        $('.le-image-controls-clear').click(function() {
            $('#' + $(this).attr('data-image-id'))
                .attr('src', $(this).attr('data-original-src'))
                .removeClass('le-edited');

            $(this).attr('disabled', true);

            if($('.le-edited').length == 0) {
                $('.le-save').attr('disabled', true);
            }
            
        });
    }

    // Initial scan
    // - If node does not have le-name data attribute, it gets registered
    // - TODO: Creates all CKEditor instances for each node
    $('.le-node').each(function(index) {
        let _this = $(this);

        // Node is not registered, register it
        if($(this).attr('data-le-name') === undefined || $(this).attr('data-le-language-incorrect') !== undefined) {
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
                    name: $(this).attr('data-le-language-incorrect') !== undefined ? $(this).attr('data-le-name') : 'le' + makeid(10),
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