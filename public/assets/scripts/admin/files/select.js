let idInp;
let fileBtn;
let fileLabel;
let filePreviewImg;
let icon;

function insertFileId(id, fileName, filePreview, filePath) {
    let dir = filePath.split('/' + fileName)[0];

    let idArray = idInp.value != '' ? JSON.parse(idInp.value) : [];

    idArray.push(id);

    idInp.value = JSON.stringify(idArray);

    let previewContainer = document.createElement('span');
    let previewImg = new Image();
    let previewImgRemoveBtn = document.createElement('button');

    previewImg.src = filePreview;
    previewImg.setAttribute('class', 'file-type-preview');

    previewImgRemoveBtn.innerHTML = '<i class="fas fa-times"></i>';
    previewImgRemoveBtn.setAttribute('class', 'preview-img-remove-btn');
    previewImgRemoveBtn.setAttribute('type', 'button');
    previewImgRemoveBtn.setAttribute('data-index', idArray.length - 1);

    previewContainer.appendChild(previewImg);
    previewContainer.appendChild(previewImgRemoveBtn);
    previewContainer.setAttribute('class', 'preview-img-container');

    filePreviewImgs.appendChild(previewContainer);
}

$(document).ready(function() {

    $('body').on('click', '.fm-open', function() {
        fileBtn         = $(this)[0];
        icon            = $(this).find('i.fas')[0];
        idInp           = $(this).find('input')[0];
        fileLabel       = $(this).find('.file-type-label')[0];
        filePreviewImgs = $(this).siblings('.image-previews')[0];

        let url = $(this).attr('data-url');
        let dir = $(this).attr('data-dir') ?? '';

        let fm = window.open(url + '&dir=' + dir, 'File manager', 'location=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes,height=600,width=1024');
        
        if (window.focus) {fm.focus()}
    });

    $('body').on('click', '.preview-img-remove-btn', function() {
        let input = $(this).parent().parent().siblings('.fm-open').find('input')[0];
        let container = $(this).parent();
        let idArray = JSON.parse(input.value);

        idArray.splice($(this).attr('data-index'), 1);

        input.value = JSON.stringify(idArray);
        container.remove();
    });

});