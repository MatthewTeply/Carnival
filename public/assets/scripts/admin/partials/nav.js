 $(document).ready(function() {
    $('.side-nav .nav-btn').click(function () {
        $('.side-nav .nav-btn.active').removeClass('active');

        $(this).addClass('active');
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

    $('.scn-nav-entity-btn').click(function() {
        $('.nav-btn').removeClass('active');
        $('#nav-btn-Entities').addClass('active');

        $('.scn-nav-entity-btn.active').removeClass('active');
        $(this).addClass('active');
    });

    $('.scn-nav-settings-btn').click(function() {
        $('.nav-btn').removeClass('active');
        $('#nav-btn-Settings').addClass('active');
    });
     
     $('.nav-right-btn').on('click', function(e) {
      $('.side-nav-right, .main-content').toggleClass("toggled");
    });
});