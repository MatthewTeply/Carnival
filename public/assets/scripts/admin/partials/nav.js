 $(document).ready(function() {
     
    function activateNavBtn(btn) {
        $('.side-nav .nav-btn.active').removeClass('active');
        $('.sub-section-btn.active').not(btn).removeClass('active');

        if(btn.hasClass('sub-section-btn')) {
            $('#nav-btn-' + btn.attr('data-section')).addClass('active');
        }
        
        btn.addClass('active');
    }

    $('.side-nav .nav-btn, .side-nav .sub-section-btn').click(function () {
        if(!$(this).hasClass('active')) {
            activateNavBtn($(this));
        }
    });

    window.addEventListener('carnival-page-changed', function(e) {
        activateNavBtn($('#nav-btn-' + e.detail.route));
    });

    $('#nav-btn-Entities').click(function(e) {
        e.preventDefault();

        $('.scn-nav-section').hide();
        $('#scn-nav-entities').show();
    });

    $('#nav-btn-Settings').click(function(e) {
        e.preventDefault();

        $('.scn-nav-section').hide();
        $('#scn-nav-settings').show();
    });

    $('.scn-nav-settings-btn').click(function() {
        $('.nav-btn').removeClass('active');
        $('#nav-btn-Settings').addClass('active');
    });
     
     $('.nav-right-btn').on('click', function(e) {
      $('.side-nav-right, .main-content').toggleClass("toggled");
    });

    $('.sub-section-toggle').on('click', function(e) {
        $('.sub-section-btn.active').removeClass('active');
    });

    var timer = new easytimer.Timer();

    timer.start({countdown: true, startValues: {hours: 3, minutes: 0, seconds: 0}});
    
    $('#nav-logout-timer').html(timer.getTimeValues().toString());

    $('.nav-logout-timer-container').click(function () {
        timer.reset();
    });

    timer.addEventListener('secondsUpdated', function (e) {
        $('#nav-logout-timer').html(timer.getTimeValues().toString());
    });

    timer.addEventListener('targetAchieved', function (e) {
        window.location.href = document.querySelector('#meta-webroot').value + 'logout';
    });

    timer.addEventListener('reset', function (e) {
        $('#nav-logout-timer').html(timer.getTimeValues().toString());
    });

    $('.popover-link').popover({
        trigger: 'hover',
        html: true,
        delay: {
            show: 300
        }
    });

    $('body').on('click', '.popover-close', function() {
        $(this).closest('.popover').popover('hide');
    });

    $('.popover-link-stay').popover({
        trigger: 'manual',
        html: true,
        delay: {
            show: 300
        }
    })
    .on('mouseenter', function () {
        var _this = this;
        
        $(this).popover('show');
        $('.popover').on('mouseleave', function () {
            $(_this).popover('hide');
        });
    }).on('mouseleave', function () {
        var _this = this;
        if (!$('.popover:hover').length) {
            $(_this).popover('hide');
        }
    });
});