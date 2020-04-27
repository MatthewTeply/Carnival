$(document).ready(function () {
    const webroot = $('#meta-webroot').val();
    const appName = $('#meta-appName').val();
    const appIsDefault = $('#meta-appIsDefault').val();

    let carnivalLink = webroot + appName;
    let isLoading = false;

    if (appIsDefault) {
        carnivalLink = webroot;
    }

    function pageLoading() {
        if (isLoading) {
            $('.logo-inner').hide();
            $('.page-loading').show();
        } else {
            $('.page-loading').hide();
            $('.logo-inner').show();
        }
    }

    function decodeHtml(html) {
        var txt = document.createElement("textarea");
        txt.innerHTML = html;
        return txt.value;
    }

    function loadPage(href) {
        if (href.includes(carnivalLink)) {
            isLoading = true;

            pageLoading();

            $.ajax({
                xhr: function () {
                    var xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener("progress", function (evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            //Do something with upload progress here
                        }
                    }, false);

                    xhr.addEventListener("progress", function (evt) {
                        if (evt.lengthComputable) {
                            var percentComplete = evt.loaded / evt.total;
                            //Do something with download progress
                        }
                    }, false);

                    return xhr;
                },
                url: href,
                method: 'GET',
                success: function (response) {
                    response = JSON.parse(response);

                    if (response.redirect) {
                        window.location.replace(response.redirect);
                    } else {
                        if (response.href) {
                            href = response.href;

                            isLoading = false;

                            loadPage(href);
                            return;
                        }

                        if (history.pushState) {
                            let urlParams = new URLSearchParams(href);

                            window.history.pushState(urlParams.toString(), 'Carnival', href);
                        } else {
                            document.location.href = href;
                        }

                        if (response.template) {
                            $('#carnival-container').html(decodeHtml(response.template));
                        }

                        if (response.title) {
                            $('title').html('Carnival &bull; ' + response.title);
                        }

                        isLoading = false;

                        pageLoading();
                    }
                }
            })
        } else {
            window.location.replace(href);
        }
    }

    $('body').on('click', 'a', function(e) {
        e.preventDefault();

        loadPage($(this).attr('href'));
    });

    $('.side-nav .nav-btn').click(function () {
        $('.side-nav .nav-btn.active').removeClass('active');

        $(this).addClass('active');
    });
});