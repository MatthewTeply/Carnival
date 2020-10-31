var selectedImage = null;
var originalContentObject = {};

// Filemanager handler
function insertFile(id, fileName, filePreview, filePath) {
    selectedImage = $('#' + selectedImage);

    selectedImage[0].src = document.querySelector('#le-meta-web-storage').value + filePath;
    
    if(selectedImage[0].src == originalContentObject[selectedImage.attr('data-le-name')]) {
        selectedImage.removeClass('le-edited');
    }

    else {
        selectedImage.addClass('le-edited');

        $('.le-image-controls-clear-' + selectedImage.attr('id')).removeAttr('disabled');

        $('.le-save').removeAttr('disabled');
        $('.le-unsaved').show();
    }
}

function checkEditedNodes(el = null) {
    if(el) {
        if(el.html() == originalContentObject[el.attr('data-le-name')]) {
            el.removeClass('le-edited');
        }

        else {
            el.addClass('le-edited');
            $('.le-save').removeAttr('disabled');
            $('.le-unsaved').show();
        }
    }

    if($('.le-edited').length == 0) {
        $('.le-save').attr('disabled', true);
        $('.le-unsaved').hide();
    }

    else {
        $('.le-save').removeAttr('disabled');
        $('.le-unsaved').show();
    }
}

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

    $(document).on('input', '.le-node', function() {
        checkEditedNodes($(this));
    });

    $(document).on('blur', '.le-selected', function() {
        $(this).removeClass('le-selected');

        checkEditedNodes();
    });

    $(document).on('click', '.le-image-controls-choose', function() {
        selectedImage = $(this).attr('data-image-id');
    });

    $(document).on('click', '.le-node', function(e) {
        e.preventDefault();

        let el = $(e.target);

        // Assign original content if it is not already assigned
        if(originalContentObject[el.attr('data-le-name')] === undefined) {
            switch(el.attr('data-le-type')) {
                case undefined:
                case 'text':
                    originalContentObject[el.attr('data-le-name')] = el.html();
                    break;
                case 'img':
                    originalContentObject[el.attr('data-le-name')] = el.attr('src');
                    break;
            }

        }

        if(el.hasClass('le-selected')) {
            return false;
        }

        switch(el.attr('data-le-type')) {
            case undefined:
            case 'text':
                $(this).on('blur', function() {
                    checkEditedNodes($(this));
                });
                break;
            case 'img':
                //selectedImage = el.attr('data-image-id');
                break;
        }

        if(!el.is(':focus')) {
            el.focus();
        }
    });

    $(document).on('click', '.le-edit', function() {
        let leName = $(this).attr('data-edit-le-name');

        $('#le-' + leName).parent().click();
    });
        
});