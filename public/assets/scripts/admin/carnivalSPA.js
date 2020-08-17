$(document).ready(function () {
    const webroot      = $('#meta-webroot').val();
    const appName      = $('#meta-appName').val();
    const appIsDefault = $('#meta-appIsDefault').val();

    let notifier = new AWN();

    let carnivalLink = webroot + 'carnival/';
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

    function displayBreadcrumb(pages) {
        document.querySelector('#top-nav-breadcrumb').innerHTML = '';
        let breadcrumb = document.querySelector('#top-nav-breadcrumb');

        let i = 0;
        for(page of pages) {
            i++;

            let li = document.createElement('li');

            let className = 'breadcrumb-item';

            if(i == pages.length) {
                className += ' active';
            }

            li.setAttribute('class', className)
            li.innerHTML = page;

            breadcrumb.appendChild(li);

        }
    }

    function loadPage(href, e = null, el = null, container = null, fade = false) {
        let hrefSplit  = href.split('/');

        if(!href.split(carnivalLink)[1]) {
            return;
        }

        let entityName = href.split(carnivalLink)[1].split('/')[0].split('?')[0].split('#')[0];

        // If link is a Carnival link, and is not a link to a file, execute function
        if (
            (href.includes(carnivalLink) && !hrefSplit[hrefSplit.length - 1].includes('.')) ||
            (el[0].hasAttribute('carnival-link') && el.attr('carnival-link') == 'true')
        ) {
            // If link element is marked as non carnival link, return
            if(el) {
                if(el.attr('carnival-link') == 'false') {
                    return;
                }
            }

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

                    if(response.success) {
                        notifier.success(response.success);
                    }

                    let pageChangedEvent = new CustomEvent('carnival-page-changed', {
                        detail: {
                            href: href,
                            route: response.route
                        }
                    });

                    if (response.redirect) {
                        window.location.replace(response.redirect);
                    } else {
                        if (response.href) {
                            href = response.href;

                            pageIsLoading = false;

                            loadPage(href);
                            window.dispatchEvent(pageChangedEvent);

                            return;
                        }

                        if (history.pushState) {
                            let urlParams = new URLSearchParams(href);

                            window.history.pushState(urlParams.toString(), 'Carnival', href);
                        } else {
                            document.location.href = href;
                        }

                        if (response.template) {
                            if(fade) {
                                $('#carnival-container').fadeOut(100);
                            }

                            $('.side-nav .scn-nav-btn.active:not(#nav-btn-' + entityName + ')').removeClass('active');
                            
                            setTimeout(() => {
                                $(container ?? '#carnival-container').html(decodeHtml(response.template));

                                if(fade) {
                                    $('#carnival-container').fadeIn(300);
                                }

                                pageLoading(false);
                                window.dispatchEvent(pageChangedEvent);

                            }, 100);

                        }

                        if (response.title) {
                            $('title').html($('title').text().split('•')[0] + ' • ' + response.title);
                        }

                        displayBreadcrumb(response.breadcrumb);

                        pageIsLoading = false;
                    }
                
                }
            });
        } 
    }

    $('body').on('click', 'a', function (e) {
        let fade = false;

        if($(this).attr('carnival-fade') == 'true') {
            fade = true;
        }

        loadPage($(this).attr('href'), e, $(this), null, fade);
    });

    window.addEventListener('popstate', function (e) {
        loadPage(e.currentTarget.location.href);
    });

    window.addEventListener('carnival-page-change', function(e) {
        loadPage(e.detail.href);
    });

});