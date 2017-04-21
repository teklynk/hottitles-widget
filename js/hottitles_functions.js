
//Hot titles container and spinner loader
function toggleSrc(rss, loc_id) {
    //Check if hottitlesCarousel container is on the page.
    if ($('#hottitlesCarousel').length) {
        $('#hottitlesCarousel').addClass('loader');
        $('#hottitlesCarousel .carousel-inner').addClass('hidden');
        $('#hottitlesCarousel .carousel-control').addClass('hidden');
        //disables the tabs until request finishes
        $('#hottitlesTabs li.hot-tab a').addClass('disable-anchor');
        setTimeout(function() {
            $.ajax({
                url: '../core/ajax/request_hottitles.php?loc_id='+loc_id+'&rssurl='+rss,
                type: 'GET',
                async: true,
                cache: true,
                timeout: 10000, //10 seconds
                success: function(result){
                    $('#hottitlesTabs li.hot-tab a').removeClass('disable-anchor');
                    $('#hottitlesCarousel').removeClass('loader');
                    $('#hottitlesCarousel .carousel-control').removeClass('hidden');
                    $('#hottitlesCarousel .carousel-inner').removeClass('hidden');
                    $('#hottitlesCarousel .carousel-inner').html(result); //show hot titles
                }
            })
        }, 500);
    }
    return false;
}