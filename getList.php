<?php 
function getHottitlesCarousel($xmlurl, $jacketSize, $dummyJackets, $maxcnt) {
    //getHottitlesCarousel("http://mylibrary.com:8080/list/dynamic/1921419/rss", 'MD', true, 30);


    $ch = curl_init();
    $timeout = 20;
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $xmlurl);    // get the url contents
    $xmldata = curl_exec($ch); // execute curl request
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    //catch and print error message
    if ($http_status != 200 || curl_errno($ch) > 0) {
        echo "HTTP status: ".$http_status.". Error loading URL. " .curl_error($ch);
        curl_close($ch);
        die();
    }

    curl_close($ch);

    $xmlfeed = simplexml_load_string($xmldata);

    $itemcount = 0;

    //Use the ls2content round-robin load balancer
    $loadBalancerArr = array(NULL, 2, 3, 4);
    $loadBalancer = $loadBalancerArr[array_rand($loadBalancerArr)];

    echo "<div class='owl-carousel owl-theme'>";
        if (strstr($xmlurl, '/econtent/')) {
            //Content server XML Lists - NYTimes

            //Gets the RSS Feed title
            $xmlrssname = "NY Times Best Sellers";

            $jacketSize = strtoupper($jacketSize);

            foreach ($xmlfeed->Book as $xmlitem) {

                $itemcount++;

                //get title node for each book
                $xmltitle = (string)$xmlitem->Title;

                //get ISBN node for each book
                $xmlisbn = (string)$xmlitem->ISBN;

                //https://ls2content2.tlcdelivers.com/tlccontent?customerid=960748&appid=ls2pac&requesttype=BOOKJACKET-MD&isbn=9781597561075
                $xmlimage = "https://ls2content$loadBalancer.tlcdelivers.com/tlccontent?customerid=999&appid=ls2pac&requesttype=BOOKJACKET-$jacketSize&isbn=$xmlisbn";

                //http://173.163.174.146:8080/?config=ysm#section=search&term=The Black Book
                $xmllink = "$setupPACURL/?config=ysm#section=search&term=$xmltitle";

                //Gets the image dimensions from the xmltheimage url as an array.
                $xmlimagesize = getimagesize($xmlimage);
                $xmlimagewidth = $xmlimagesize[0];
                $xmlimageheight = $xmlimagesize[1];

                echo "<div class='item'>";

                //Check if has book jacket based on the image size (1x1)
                if ($xmlimageheight > '1' && $xmlimagewidth > '1') {
                    echo "<a href='" . htmlspecialchars($xmllink, ENT_QUOTES) . "' title='" . htmlspecialchars($xmltitle, ENT_QUOTES) . "' target='_blank' data-resource-isbn='" . $xmlisbn . "' data-item-count='" . $itemcount . "'><img src='" . htmlspecialchars($xmlimage, ENT_QUOTES) . "' class='img-responsive center-block $jacketSize'></a>";
                } else {
                    if ($dummyJackets == true) {
                        //TLC dummy book jacket img
                        echo "<a href='" . htmlspecialchars($xmllink, ENT_QUOTES) . "' title='" . htmlspecialchars($xmltitle, ENT_QUOTES) . "' target='_blank' data-resource-isbn='" . $xmlisbn . "' data-item-count='" . $itemcount . "'><span class='dummy-title'>" . htmlspecialchars($xmltitle, ENT_QUOTES) . "</span><img class='dummy-jacket $jacketSize img-responsive center-block' src='../core/images/gray-bookjacket-md.png'></a>";
                    }
                }

                echo "</div>";

                //stop parsing xml once it reaches the max count
                if ($itemcount == $maxcnt) {
                    break;
                }
            }
        } elseif (strstr($xmlurl, '/list/')) {
            //LS2PAC Saved Search XML Lists

            //Gets the RSS Feed title
            $xmlrssname = $xmlfeed->channel->title;
            $xmlrssname = trim(str_replace('LS2 PAC:', '', $xmlrssname));

            foreach ($xmlfeed->channel->item as $xmlitem) {

                $itemcount++;

                //get title node for each book
                $xmltitle = (string)$xmlitem->title;

                //get url for each book
                $xmllink = (string)$xmlitem->link;

                //Get the ResourceID from the xmllink
                parse_str($xmllink, $xmllinkArray);
                $xmlResourceId = $xmllinkArray['resourceId'];

                //get image url from img tag in the description node
                preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', (string)$xmlitem->description, $xmltheimage);

                //set the image url. clean the image url string
                $xmlimage = $xmltheimage[1];

                //Replace http with https
                $xmlimage = trim(str_replace('http:', 'https:', $xmlimage));

                //Use the ls2content round-robin load balancer
                $xmlimage = trim(str_replace("ls2content", "ls2content".$loadBalancer."", $xmlimage));

                if ($jacketSize == 'SM') {
                    $xmlimage = trim(str_replace('BOOKJACKET-MD', 'BOOKJACKET-SM', $xmlimage));
                    $xmlimage = trim(str_replace('BOOKJACKET-LG', 'BOOKJACKET-SM', $xmlimage));
                } elseif ($jacketSize == 'MD') {
                    $xmlimage = trim(str_replace('BOOKJACKET-SM', 'BOOKJACKET-MD', $xmlimage));
                    $xmlimage = trim(str_replace('BOOKJACKET-LG', 'BOOKJACKET-MD', $xmlimage));
                } elseif ($jacketSize == 'LG') {
                    $xmlimage = trim(str_replace('BOOKJACKET-SM', 'BOOKJACKET-LG', $xmlimage));
                    $xmlimage = trim(str_replace('BOOKJACKET-MD', 'BOOKJACKET-LG', $xmlimage));
                }

                //Gets the image dimensions from the xmltheimage url as an array.
                $xmlimagesize = getimagesize($xmltheimage[1]);
                $xmlimagewidth = $xmlimagesize[0];
                $xmlimageheight = $xmlimagesize[1];

                echo "<div class='item'>";

                //Check if has book jacket based on the image size (1x1)
                if ($xmlimageheight > '1' && $xmlimagewidth > '1') {
                    echo "<a href='" . htmlspecialchars($xmllink, ENT_QUOTES) . "' title='" . htmlspecialchars($xmltitle, ENT_QUOTES) . "' target='_blank' data-resource-id='" . $xmlResourceId . "' data-item-count='" . $itemcount . "'><img src='" . htmlspecialchars($xmlimage, ENT_QUOTES) . "' class='img-responsive center-block $jacketSize'></a>";
                } else {
                    if ($dummyJackets == true) {
                        //TLC dummy book jacket img
                        echo "<a href='" . htmlspecialchars($xmllink, ENT_QUOTES) . "' title='" . htmlspecialchars($xmltitle, ENT_QUOTES) . "' target='_blank' data-resource-id='" . $xmlResourceId . "' data-item-count='" . $itemcount . "'><span class='dummy-title'>" . htmlspecialchars($xmltitle, ENT_QUOTES) . "</span><img class='dummy-jacket $jacketSize img-responsive center-block' src='../core/images/gray-bookjacket-".strtolower($jacketSize).".png'></a>";
                    }
                }

                echo "</div>";

                //stop parsing xml once it reaches the max count
                if ($itemcount == $maxcnt) {
                    break;
                }

            } //end for loop
        }
    echo "</div>";
}
//

if (!empty($_GET['rssurl'])) {

?>
<!DOCTYPE html>
<html lang="en">
<head>

</head>
<body>
    <script type="text/javascript">
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
            autoplayTimeout: <?php echo $carouselSpeed; ?>,
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
    </script>

<?php

    $rssUrl = $_GET['rssurl'];
    $jacketSize = strtoupper($_GET['size']);
    $blankJackets  =  $_GET['blanks'];
    $maxCount = $_GET['max'];

    //example: getHottitlesCarousel("http://beacon.tlcdelivers.com:8080/list/dynamic/1921419/rss", 'MD', true, 30);
    getHottitlesCarousel($rssUrl, $jacketSize, $blankJackets, $maxCount);

} else {

    die('URL not found');

}
?>
</body>
</html>
