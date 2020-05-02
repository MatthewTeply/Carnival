$(document).ready(function () {
    const webroot = $('#meta-webroot').val();
    const appName = $('#meta-appName').val();
    const appIsDefault = $('#meta-appIsDefault').val();

    let notifier = new AWN();

    let carnivalLink = webroot + appName;
    let pageIsLoading = false;

    if (appIsDefault) {
        carnivalLink = webroot;
    }

    function pageLoading(loading = null) {
        if(loading === null) {
            loading = pageIsLoading;
        }

        if (loading) {
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

    function loadPage(href, e = null) {
        let hrefSplit  = href.split('/');
        let entityName = href.split(carnivalLink)[1].split('/')[0].split('?')[0].split('#')[0];

        // If link is a Carnival link, and is not a link to a file, execute function
        if (href.includes(carnivalLink) && !hrefSplit[hrefSplit.length - 1].includes('.')) {
            // If event is provided, preventDefault
            if(e) {
                e.preventDefault();
            }

            pageIsLoading = true;

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
                    try {
                        response = JSON.parse(response);
                    }

                    catch(e) {
                        notifier.alert('Page could not be loaded! [Error: 1]');
                        console.error(response);

                        pageLoading(false);
                        return;
                    }

                    if(response.error) {
                        notifier.alert(response.error);
                        
                        pageLoading(false);
                        return;
                    }

                    if (response.redirect) {
                        window.location.replace(response.redirect);
                    } else {
                        if (response.href) {
                            href = response.href;

                            pageIsLoading = false;

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
                            $('.side-nav .nav-btn.active:not(#nav-btn-' + entityName + ')').removeClass('active');
                            $('#nav-btn-' + entityName).addClass('active');

                            $('#carnival-container').html(decodeHtml(response.template));

                            pageLoading(false);
                        }

                        if (response.title) {
                            $('title').html('Carnival &bull; ' + response.title);
                        }

                        pageIsLoading = false;
                    }
                
                }
            });
        } 
    }

    $('body').on('click', 'a', function (e) {
        loadPage($(this).attr('href'), e);
    });

    window.addEventListener('popstate', function (e) {
        loadPage(e.currentTarget.location.href);
    });

    window.addEventListener('carnival-page-change', function(e) {
        loadPage(e.detail.href);
    });

    $('.side-nav .nav-btn').click(function () {
        $('.side-nav .nav-btn.active').removeClass('active');

        $(this).addClass('active');
    });
});