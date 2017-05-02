<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TLC - Hot Titles Carousel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/css/bootstrap-theme.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/assets/owl.carousel.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/assets/owl.theme.default.min.css" />
    <link rel="stylesheet" href="css/hottitles.styles.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.7/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.2.1/owl.carousel.min.js"></script>
    <script src="js/hottitles.functions.min.js"></script>
</head>
<body>
<?php
function getHottitlesListTitle($xmlurl) {
    global $xmlrssname;

    $ch = curl_init();
    $timeout = 20;
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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

    //Gets the RSS Feed title
    $xmlrssname = $xmlfeed->channel->title;
    $xmlrssname = trim(str_replace('LS2 PAC:', '', $xmlrssname));
}

function getHottitlesCarousel($xmlurl, $jacketSize, $dummyJackets, $maxcnt) {
    //example: getHottitlesCarousel("http://beacon.tlcdelivers.com:8080/list/dynamic/1921419/rss", 'MD', true, 30);

    $ch = curl_init();
    $timeout = 20;
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
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
        if (strstr($xmlurl, '/list/')) {
            //LS2PAC Saved Search XML Lists

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
                    if ($dummyJackets == 'true') {
                        //TLC dummy book jacket img
                        echo "<a href='" . htmlspecialchars($xmllink, ENT_QUOTES) . "' title='" . htmlspecialchars($xmltitle, ENT_QUOTES) . "' target='_blank' data-resource-id='" . $xmlResourceId . "' data-item-count='" . $itemcount . "'><span class='dummy-title'>" . htmlspecialchars($xmltitle, ENT_QUOTES) . "</span><img class='dummy-jacket $jacketSize img-responsive center-block' src='images/gray-bookjacket-".strtolower($jacketSize).".png'></a>";
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

if (!empty($_GET['urls'])) {

    $hottitlesUrl = $_GET['urls'];
    $hottitlesUrlArray = explode(',', $_GET['urls']);
    $jacketSize = strtoupper($_GET['jacketsize']);
    $dummyJackets = $_GET['showmissingjackets'];
    $maxCount = $_GET['maxcount'];
    $hottitlesCount = 0;

    echo "<div class='container-fluid'>";
    echo "<div class='well'>";

    //Tabs
    echo "<div id='hottitlesTabs'>";
    echo "<div class='panel text-center'>";
    echo "<ul class='nav nav-pills center-tabs'>";

        foreach ($hottitlesUrlArray as $hotUrl) {
            $hottitlesCount ++;

            getHottitlesListTitle($hotUrl); //get the title from the rss feed

            if ($hottitlesCount == $_GET['listnum']) {
                $hotActive = 'active';
            } else {
                $hotActive = '';
            }

            echo "<li class='hot-tab $hotActive'><a target='_self' href='getlist.php?urls=".$hottitlesUrl."&jacketsize=".$jacketSize."&maxcount=".$maxCount."&showmissingjackets=".$dummyJackets."&listnum=".$hottitlesCount."'>".$xmlrssname."</a></li>";
        }

    echo "</ul>";
    echo "</div>";
    echo "</div>";

    //Carousel
    echo "<div class='carousel slide loader-size-$jacketSize' id='hottitlesCarousel'>";
    echo "<div class='carousel-inner $jacketSize'>";

        if ($_GET['listnum'] == '') {
            $hottitlesUrlArrayCnt = 0;
        } else {
            $hottitlesUrlArrayCnt = $_GET['listnum'] - 1;
        }

        //example: getHottitlesCarousel("http://beacon.tlcdelivers.com:8080/list/dynamic/1921419/rss", 'MD', 30);
        getHottitlesCarousel($hottitlesUrlArray[$hottitlesUrlArrayCnt], $jacketSize, $dummyJackets, $maxCount);

    echo "</div>";
    echo "</div>";

    echo "</div>"; //well
    echo "</div>"; //container

} else {

    die('URL not found or parameters are not correct');

}
?>
</body>
</html>