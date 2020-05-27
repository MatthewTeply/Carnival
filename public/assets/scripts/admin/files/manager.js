window.createDirModal = new Vue({
    el: '#create-dir-modal',
    delimiters: ['${', '}'],
    data: {
        dirName: ''
    }
});

let mainContentElement = document.querySelector('.main-content');

interact('.dir, .file').draggable({
    inertia: true,
    modifiers: [
        interact.modifiers.restrictRect({
            restriction: 'parent',
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

interact('.dir').dropzone({
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
        var draggableElement = event.relatedTarget;
        var dropzoneElement  = event.target;

        let from       = draggableElement.getAttribute('data-file-path') ?? draggableElement.getAttribute('data-dir-path');
        let to         = dropzoneElement.getAttribute('data-dir-path') + '/' + (draggableElement.getAttribute('data-file-name') ?? draggableElement.getAttribute('data-dir-name'));
        let currentDir = document.querySelector('#current-dir').value;

        console.log('From: ' + from);
        console.log('To: ' + to);
        console.log('Current dir: ' + currentDir);

        event.target.classList.remove('dir-dragged-over');

        let notifier = new AWN();

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

                    if(response.href && response.success) {
                        let pageChangeEvent = new CustomEvent('carnival-page-change', {
                            detail: {
                                href: response.href
                            }
                        });

                        notifier.success(success);

                        window.dispatchEvent(pageChangeEvent);
                    }
                }

                catch(e) {
                    console.log(response);
                    notifier.alert('Failed moving file!');
                }
            }
        });
    }
});