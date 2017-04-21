<?php

function getHottitlesCarousel($xmlurl, $jacketSize, $dummyJackets, $maxcnt) {
    //getHottitlesCarousel("http://mylibrary.com:8080/list/dynamic/1921419/rss", 'MD', true, 30);
    global $customerId;
    global $setupPACURL;

    $ch = curl_init();
    $timeout = 10;
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_URL, $xmlurl);    // get the url contents
    $xmldata = curl_exec($ch); // execute curl request
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Check if any error occurred
    if (curl_errno($ch) > 0) {
        echo "Error loading URL. " .curl_error($ch);
        curl_close($ch);
        die();
    }

    //catch and print error message
    if ($http_status != 200) {
        echo "HTTP status: ".$http_status.". Error loading URL. " .curl_error($ch);
        curl_close($ch);
        die();
    }

    curl_close($ch);

    $xmlfeed = simplexml_load_string($xmldata);

    $itemcount = 0;

    echo "<div class='owl-carousel owl-theme'>";
    if (strstr($xmlurl, 'econtent')) {
        //Content server XML Lists
        foreach ($xmlfeed->Book as $xmlitem) {

            $itemcount++;

            //get title node for each book. clean title string
            $xmltitle = (string)$xmlitem->Title;

            //get ISBN node for each book
            $xmlisbn = (string)$xmlitem->ISBN;

            //https://ls2content.tlcdelivers.com/tlccontent?customerid=960748&appid=ls2pac&requesttype=BOOKJACKET-MD&isbn=9781597561075
            $xmlimage = "https://ls2content.tlcdelivers.com/tlccontent?customerid=$customerId&appid=ls2pac&requesttype=BOOKJACKET-$jacketSize&isbn=$xmlisbn";

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
    } elseif (strstr($xmlurl, 'list')) {
        //LS2PAC Saved Search XML Lists
        foreach ($xmlfeed->channel->item as $xmlitem) {

            $itemcount++;

            //get title node for each book. clean title string
            $xmltitle = (string)$xmlitem->title;

            //get url for each book. clean link string
            $xmllink = (string)$xmlitem->link;

            //Get the ResourceID from the xmllink
            parse_str($xmllink, $xmllinkArray);
            $xmlResourceId = $xmllinkArray['resourceId'];

            //get image url from img tag in the description node
            preg_match('/< *img[^>]*src *= *["\']?([^"\']*)/i', (string)$xmlitem->description, $xmltheimage);

            //set the image url. clean the image url string
            $xmlimage = $xmltheimage[1];
            //Remove http(s) and just use //
            //$xmlimage = trim(str_replace(array('http:', 'https:'), '', $xmlimage));
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

function getHottitlesTabs(){
    global $hottitlesTile;
    global $hottitlesUrl;
    global $hottitlesLoadFirstUrl;
    global $hottitlesLocID;
    global $hottitlesTabs;
    global $hottitlesCount;
    global $hottitlesHeading;
    global $locTypes;
    global $db_conn;

    //get the heading value from setup table
    $sqlHottitlesSetup = mysqli_query($db_conn, "SELECT hottitlesheading, loc_id FROM setup WHERE loc_id=" . $_GET['loc_id'] . " ");
    $rowHottitlesSetup = mysqli_fetch_array($sqlHottitlesSetup);
    $hottitlesHeading = $rowHottitlesSetup['hottitlesheading'];

    //get location type from locations table
    $sqlLocations = mysqli_query($db_conn, "SELECT id, name, type FROM locations WHERE id=" . $_GET['loc_id'] . " ");
    $rowLocations = mysqli_fetch_array($sqlLocations);

    if ($rowLocations['type'] == '' || $rowLocations['type'] == NULL || $rowLocations['type'] == $locTypes[0]){
        $hottitlesLocType = $rowLocations['type'];
        $locTypeWhere = "loc_type IN ('".$locTypes[0]."', 'All') AND";
    } else {
        $hottitlesLocType = $rowLocations['type'];
        $locTypeWhere = "loc_type IN ('".$hottitlesLocType."', 'All') AND";
    }

    //get the default value from setup table
    $sqlHottitlesSetup = mysqli_query($db_conn, "SELECT hottitlesheading, hottitles_use_defaults, loc_id FROM setup WHERE loc_id=" . $_GET['loc_id'] . " ");
    $rowHottitlesSetup = mysqli_fetch_array($sqlHottitlesSetup);

    $sqlHottitles = mysqli_query($db_conn, "SELECT id, title, url, loc_type, sort, active, loc_id FROM hottitles-widget WHERE active='true' AND $locTypeWhere loc_id=" . $_GET['loc_id'] . " ORDER BY sort ASC");
    $hottitlesLocID = $_GET['loc_id'];

    //use default location
    if ($rowHottitlesSetup['hottitles_use_defaults'] == "true" || $rowHottitlesSetup['hottitles_use_defaults'] == "" || $rowHottitlesSetup['hottitles_use_defaults'] == NULL) {
        $sqlHottitles = mysqli_query($db_conn, "SELECT id, title, url, loc_type, sort, active, loc_id FROM hottitles-widget WHERE active='true' AND $locTypeWhere loc_id=1 ORDER BY sort ASC");
        $hottitlesLocID = 1;
    }

    $hottitlesCount = 0;
    while ($rowHottitles = mysqli_fetch_array($sqlHottitles)) {

        $hottitlesSort = trim($rowHottitles['sort']);
        $hottitlesTile = trim($rowHottitles['title']);
        $hottitlesUrl = trim($rowHottitles['url']);
        $hottitlesLocType = trim($rowHottitles['loc_type']);
        $hottitlesCount ++;

        //Set active tab on initial page load where count=1
        if ($hottitlesCount == 1) {
            $hotActive = 'active';
            $hottitlesLoadFirstUrl = $hottitlesUrl;
        } else {
            $hotActive = '';
        }

        if ($hottitlesCount > 0) {
            $hottitlesTabs .=  "<li class='hot-tab $hotActive'><a data-toggle='tab' onclick=\"toggleSrc('$hottitlesUrl', '$hottitlesLocID', '$hottitlesCount');\">$hottitlesTile</a></li>";
        }
    }
}


?>