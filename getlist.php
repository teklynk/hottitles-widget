<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="index,follow">
    <meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=2.0,user-scalable=yes">
    <title>TLC - Hot Titles Carousel</title>
    <!-- Core CSS Libraries -->
    <link rel="stylesheet" type="text/css" href="css/hottitles.min.css" />
    <link rel="stylesheet" type="text/css" href="css/font-awesome.min.css" />
    <link rel="stylesheet" type="text/css" href="css/hottitles.styles.min.css" />
    <!-- Core JS Libraries -->
    <script src="js/hottitles.min.js"></script>
    <script src="js/hottitles.functions.min.js"></script>
</head>
<body>
<?php
function getHottitlesListTitle($xmlurl) {
    global $xmlrssname;

    $ch = curl_init();
    $timeout = 10;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_URL, $xmlurl);    // get the url contents
    $xmldata = curl_exec($ch); // execute curl request
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    //catch and print error message
    if ($http_status != 200 || curl_errno($ch) > 0) {
        echo "HTTP status: ".$http_status.". Error loading URL. " .curl_error($ch) . PHP_EOL;
        echo "Could not get title from RSS feed." . PHP_EOL;
        curl_close($ch);
        die();
    }

    curl_close($ch);

    $xmlfeed = simplexml_load_string($xmldata);

    //Gets the RSS Feed title
    if (strstr($xmlurl, '/econtent/')) {
        $xmlrssname = "NY Times Best Sellers";
    } else {
        $xmlrssname = $xmlfeed->channel->title;
        $xmlrssname = trim(str_replace('LS2 PAC:', '', $xmlrssname));
    }
}

function getHottitlesCarousel($xmlurl, $jacketSize, $dummyJackets, $maxcnt, $custId, $pacUrl) {
    //example: getHottitlesCarousel('http://beacon.tlcdelivers.com:8080/list/dynamic/1921419/rss', 'MD', 'true', 30, 999999, 'https://mylibrary.com:8080');

    $ch = curl_init();
    $timeout = 10;
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_URL, $xmlurl);    // get the url contents
    $xmldata = curl_exec($ch); // execute curl request
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    //catch and print error message
    if ($http_status != 200 || curl_errno($ch) > 0) {
        echo "HTTP status: ".$http_status.". Error loading URL. " .curl_error($ch) . PHP_EOL;
        echo "Could not read " . $xmlurl . PHP_EOL;
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
            //http://content.tlcdelivers.com/econtent/xml/NYTimes.xml

            //Gets the RSS Feed title
            $xmlrssname = "NY Times Best Sellers";

            $pacUrl = trim($_GET['pacurl']);

            $jacketSize = strtoupper($jacketSize);

            foreach ($xmlfeed->Book as $xmlitem) {

                $itemcount++;

                //get title node for each book
                $xmltitle = (string)$xmlitem->Title;

                //get ISBN node for each book
                $xmlisbn = (string)$xmlitem->ISBN;

                //https://ls2content2.tlcdelivers.com/tlccontent?customerid=960748&appid=ls2pac&requesttype=BOOKJACKET-MD&isbn=9781597561075
                $xmlimage = "https://ls2content$loadBalancer.tlcdelivers.com/tlccontent?customerid=$custId&appid=ls2pac&requesttype=BOOKJACKET-$jacketSize&isbn=$xmlisbn";

                //http://173.163.174.146:8080/?config=ysm#section=search&term=The Black Book
                $xmllink = "$pacUrl/?config=ysm#section=search&term=$xmltitle";

                //Gets the image dimensions from the xmltheimage url as an array.
                $xmlimagesize = getimagesize($xmlimage);
                $xmlimagewidth = $xmlimagesize[0];
                $xmlimageheight = $xmlimagesize[1];

                echo "<div class='item'>";

                //Check if has book jacket based on the image size (1x1)
                if ($xmlimageheight > '1' && $xmlimagewidth > '1') {
                    echo "<a href='" . htmlspecialchars($xmllink, ENT_QUOTES) . "' title='" . htmlspecialchars($xmltitle, ENT_QUOTES) . "' target='_blank' data-resource-isbn='" . $xmlisbn . "' data-item-count='" . $itemcount . "'><img src='" . htmlspecialchars($xmlimage, ENT_QUOTES) . "' class='img-responsive center-block $jacketSize'></a>";
                } else {
                    if ($dummyJackets == 'true') {
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

//Check if customerid is set up on the content server
if (!empty($_GET['customerid'])) {

    $custId = trim($_GET['customerid']);
    $checkUrl = 'https://ls2content.tlcdelivers.com/tlccontent?customerid='.$custId.'&appid=ls2pac&requesttype=BOOKJACKET-MD&isbn=9780470167779';

    $ch = curl_init($checkUrl);
    curl_setopt($ch,  CURLOPT_RETURNTRANSFER, TRUE);
    $response = curl_exec($ch);
    //Check for 404 (file not found) OR 403 (access denied)
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_status != 200 || curl_errno($ch) > 0) {
        echo "HTTP status: " . $http_status . ". Error loading URL. " . curl_error($ch) . PHP_EOL;
        echo "Not a valid customer id " . $custId . PHP_EOL;
        curl_close($ch);
        die();
    }

    curl_close($ch);

} else {

    die('URL not found or parameters are not correct.');
}

if (!empty($_GET['urls'] && $_GET['customerid'])) {

    $hottitlesUrl = $_GET['urls'];
    $hottitlesUrlArray = explode(',', $_GET['urls']);
    $custId = trim($_GET['customerid']);

    if (!empty($_GET['jacketsize'])) {
        $jacketSize = strtoupper(trim($_GET['jacketsize']));
    } else {
        $jacketSize = 'MD';
    }

    if (!empty($_GET['showmissingjackets'])) {
        $dummyJackets = trim($_GET['showmissingjackets']);
    } else {
        $dummyJackets = 'true';
    }

    if (!empty($_GET['maxcount'])) {
        $maxCount = trim($_GET['maxcount']);
    } else {
        $maxCount = 50;
    }
    if (!empty($_GET['pacurl'])) {
        $pacUrl = trim($_GET['pacurl']);
    }

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

            echo "<li class='hot-tab $hotActive'><a target='_self' href='getlist.php?urls=".$hottitlesUrl."&customerid=".$custId."&pacurl=".$pacUrl."&jacketsize=".$jacketSize."&maxcount=".$maxCount."&showmissingjackets=".$dummyJackets."&listnum=".$hottitlesCount."'>".$xmlrssname."</a></li>";
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

        //example: getHottitlesCarousel('http://beacon.tlcdelivers.com:8080/list/dynamic/1921419/rss[0]', 'MD', 'true', 30, 999999, 'https://mylibrary.com:8080');
        getHottitlesCarousel($hottitlesUrlArray[$hottitlesUrlArrayCnt], $jacketSize, $dummyJackets, $maxCount, $custId, $pacUrl);

    echo "</div>";
    echo "</div>";

    echo "</div>"; //well
    echo "</div>"; //container

} else {

    die('URL not found or parameters are not correct.');

}
?>
</body>
</html>