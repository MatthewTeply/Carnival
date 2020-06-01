let idInp;
let fileBtn;
let fileLabel;
let filePreviewImg;
let icon;

function insertFileId(id, fileName, filePreview, filePath) {
    let dir = filePath.split('/' + fileName)[0];
    
    idInp.value         = id;
    fileLabel.innerHTML = fileName;

    icon.style.display           = 'none';
    filePreviewImg.style.display = 'block';
    filePreviewImg.src           = filePreview;
    fileBtn.setAttribute('data-dir', dir == fileName ? '' : dir);
}

$(document).ready(function() {

    $('body').on('click', '.fm-open', function() {
        fileBtn        = $(this)[0];
        icon           = $(this).find('i.fas')[0];
        idInp          = $(this).find('input')[0];
        fileLabel      = $(this).find('.file-type-label')[0];
        filePreviewImg = $(this).find('.file-type-preview')[0];

        let url = $(this).attr('data-url');
        let dir = $(this).attr('data-dir') ?? '';

        let fm = window.open(url + '&dir=' + dir, 'File manager', 'location=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes,height=600,width=1024');
        
        if (window.focus) {fm.focus()}
    });

});