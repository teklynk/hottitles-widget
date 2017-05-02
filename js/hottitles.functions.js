//Hot titles container and spinner loader
$(document).ready(function(){
    $('#hottitlesTabs li.hot-tab a').click(function(){
        $('#hottitlesTabs li.hot-tab a').addClass('disable-anchor');
        $('#hottitlesTabs li.hot-tab').removeClass('active');
        $('#hottitlesCarousel').addClass('loader');
        $('#hottitlesCarousel .carousel-inner').addClass('hidden');
        $('#hottitlesCarousel .carousel-control').addClass('hidden');
    });

    $('#hottitlesTabs li.hot-tab a').removeClass('disable-anchor');
    $('#hottitlesCarousel').removeClass('loader');
    $('#hottitlesCarousel .carousel-inner').removeClass('hidden');
    $('#hottitlesCarousel .carousel-control').removeClass('hidden');

    //Hot titles carousel
    $('.owl-carousel').owlCarousel({
        center: true,
        loop: true,
        margin: 10,
        nav: true,
        dots: false,
        autoWidth: true,
        //adds bootstrap nav buttons
        navText: [
            '<span class="left carousel-control" data-slide="prev"><i class="icon-prev"></i></span>',
            '<span class="right carousel-control" data-slide="next"><i class="icon-next"></i></span>'
        ],
        autoplay: true,
        autoplayTimeout: 5000,
        autoplayHoverPause: true,
        items: 8,
        responsive:{
            0:{
                items:1
            },
            600:{
                items:3
            },
            1000:{
                items:5
            }
        }
    });
});