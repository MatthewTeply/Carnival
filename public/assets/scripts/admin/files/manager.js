window.createDirModal = new Vue({
    el: '#create-dir-modal',
    delimiters: ['${', '}'],
    data: {
        dirName: ''
    }
});

let mainContentElement = document.querySelector('.main-content');
let notifier = new AWN();

interact('.dir, .file').draggable({
    inertia: true,
    modifiers: [
        interact.modifiers.restrictRect({
            endOnly: true
        })
    ],
    autoScroll: true,
    listeners: {
        // call this function on every dragmove event
        move: dragMoveListener,
        start(event) {
            event.target.classList.add('file-dragged');
        },
        end(event) {
            event.target.setAttribute('data-x', 0);
            event.target.setAttribute('data-y', 0);

            event.target.style.webkitTransform =
                event.target.style.transform =
                'translate(0, 0)';

            event.target.classList.remove('file-dragged');

            event.preventDefault();
        }
    }
});

function dragMoveListener(event) {
    var target = event.target
    // keep the dragged position in the data-x/data-y attributes
    var x = (parseFloat(target.getAttribute('data-x')) || 0) + event.dx;
    var y = (parseFloat(target.getAttribute('data-y')) || 0) + event.dy;

    // translate the element
    target.style.webkitTransform =
        target.style.transform =
        'translate(' + x + 'px, ' + y + 'px)';

    // update the posiion attributes
    target.setAttribute('data-x', x);
    target.setAttribute('data-y', y);
}

// this function is used later in the resizing and gesture demos
mainContentElement.dragMoveListener = dragMoveListener

interact('.dir, .fm-breadcrumb').dropzone({
    accept: '.file, .dir',
    ondragenter: function (event) {
        var dropzoneElement  = event.target;

        // feedback the possibility of a drop
        dropzoneElement.classList.add('dir-dragged-over');
    },
    ondragleave: function (event) {
        // remove the drop feedback style
        event.target.classList.remove('dir-dragged-over');
    },
    ondrop: (event) => {
        console.log('Dropped');

        var draggableElement = event.relatedTarget;
        var dropzoneElement  = event.target;

        let from       = draggableElement.getAttribute('data-file-path') ?? draggableElement.getAttribute('data-dir-path');
        let to         = dropzoneElement.getAttribute('data-dir-path') + '/' + (draggableElement.getAttribute('data-file-name') ?? draggableElement.getAttribute('data-dir-name'));
        let currentDir = document.querySelector('#current-dir').value;

        event.target.classList.remove('dir-dragged-over');

        $.ajax({
            url: document.querySelector('#fm-url').value + '/move',
            method: 'POST',
            data: {
                from,
                to,
                currentDir
            },
            success: (response) => {
                try {
                    response = JSON.parse(response);

                    let pageChangeEvent = new CustomEvent('carnival-page-change', {
                        detail: {
                            href: response.href
                        }
                    });

                    notifier.success(response.success);

                    window.dispatchEvent(pageChangeEvent);
                }

                catch(e) {
                    console.error(response);
                    notifier.alert('Failed moving file!');
                }
            }
        });
    }
});

$('body').on('dblclick', '.file', function() {
    let id          = $(this).attr('data-file-id');
    let fileName    = $(this).attr('data-file-name');
    let filePreview = $(this).attr('data-file-preview');
    let filePath    = $(this).attr('data-file-path');

    window.opener.insertFileId(id, fileName, filePreview, filePath);
    window.close();
});

$('body').on('click', '#fm-files-upload-btn', function() {
    $('#fm-files-upload').click();
});

$('body').on('change', '#fm-files-upload', function(e) {
    e.preventDefault();

    $('#fm-files-upload-btn').attr('disabled', 'disabled');

    $('#fm-files-upload-btn .label').hide();
    $('#fm-files-upload-btn .loading').show();

    var formData = new FormData($(this).parents('form')[0]);

    $.ajax({
        url: $('#fm-url').val() + '/upload',
        type: 'POST',
        xhr: function() {
            var myXhr = $.ajaxSettings.xhr();
            return myXhr;
        },
        success: function (response) {
            try {
                response = JSON.parse(response);

                let pageChangeEvent = new CustomEvent('carnival-page-change', {
                    detail: {
                        href: response.href
                    }
                });

                notifier.success(response.success);

                window.dispatchEvent(pageChangeEvent);
            }

            catch(e) {
                console.error(response);
                notifier.alert('Failed uploading the file!');
            }

            $('#fm-files-upload-btn').removeAttr('disabled');

            $('#fm-files-upload-btn .loading').hide();
            $('#fm-files-upload-btn .label').show();
        },
        data: formData,
        cache: false,
        contentType: false,
        processData: false
    });
});

$('body').on('click', '.menu-toggle-container .toggler', function() {
    $(this).toggleClass('fa-ellipsis-h');    
    $(this).toggleClass('fa-times');  

    $(this).siblings('.content').toggle();
});

$('body').on('click', '.file', function() {
    $('.file').removeClass('focused');
    $(this).addClass('focused');
});