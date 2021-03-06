<?php

// Dmitry Sandalov
// Twitter 2 Google Plus CrossPost PHP script
// v0.2

// Credits: 
// Luka Pusic luka@pusic.si
// Vladimir Smirnoff http://orl.sumy.ua mail@smirnoff.sumy.ua
// Kichrum http://Kichrum.org.ua

// (!) Works only with Google 2-step auth turned off
// (!) The code needs refactoring/rewriting, works for me now.
// (!) Needs 2 blank 600 files: vtweet.txt, cookie.txt

// Required Parameters
$email = 'your@mail.com'; // Your mail (works also with Google Apps accounts)
$pass = 'PassHere'; // Your password
define ('TWITTER_USERNAME','YourTwitter'); // Twitter Username
define ('PREPEND_WORD','Twitter.com/YourTwitter ');  // Text before each google+ post

// Optional Parameters
$pageid = false;// Enter pageid if you want to post to a page.
$cookies = 'cookie.txt'; // Path to cookie file
$sleeptime = 0; // sleeptime is an optional timeout parameter which makes us look less suspicious to Google
$uagent = 'Mozilla/4.0 (compatible; MSIE 5.0; S60/3.0 NokiaN73-1/2.0(2.0617.0.0.7) Profile/MIDP-2.0 Configuration/CLDC-1.1)';
$pc_uagent = 'Mozilla/5.0 (X11; Linux x86_64; rv:7.0.1) Gecko/20100101 Firefox/7.0.1';
$debug = FALSE;

// MAIN BLOCK  login_data() just collects login form info, 
// login($postdata) logs you in and you can do pretty much anything you want from here on
// Get last Twitter status from RSS
$rss = simplexml_load_file('http://twitter.com/statuses/user_timeline/'.TWITTER_USERNAME.'.rss');
foreach ($rss->channel->item as $status) {
        if ((strpos($status->title,'@')) and // Twitter @ whitelist
                ( (strpos($status->title,'[pic]')) or
                  (strpos($status->title,"I'm at")) or
                  (strpos($status->title,"YouTube"))
                )
           )
        { $last_status_twitter = str_replace (TWITTER_USERNAME.': ','',$status->title); break; }
        if (strpos($status->title,'@')) continue; // Twitter @ filter
        $last_status_twitter = str_replace (TWITTER_USERNAME.': ','',$status->title); break;
}
$status = $last_status_twitter;

# Read last updated status from vtweet.txt
$f=fopen(dirname(__FILE__).'/vtweet.txt','r');
$last_status_gplus = fread($f,512);
fclose($f);

@unlink($cookies); //delete previous cookie file if exists
touch($cookies); //create a cookie file

if (($last_status_gplus != $last_status_twitter) && ($last_status_twitter != NULL)) {
	login(login_data());
	sleep($sleeptime);
	if ($pageid) {
	    update_page_status();
	} else {
	    update_profile_status();
	} //update status with $GLOBAL['status'];
	sleep($sleeptime);
	logout(); //optional - log out

        $f=fopen(dirname(__FILE__).'/vtweet.txt','w');
	fwrite($f,$last_status_twitter); // Mark status as updated in file
        fclose($f);
        echo 'Updated.\n';
}
else echo 'No update needed.\n';

// GET: http://plus.google.com/, Parse the webpage and collect form data
function login_data() { 
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, "https://plus.google.com/");
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $buf = utf8_decode(html_entity_decode(curl_exec($ch)));
    curl_close($ch);

    echo "\n[+] Sending GET request to: https://plus.google.com/\n\n";
    $toreturn = '';
    $doc = new DOMDocument;
    $doc->loadxml($buf);
    $inputs = $doc->getElementsByTagName('input');
    foreach ($inputs as $input) {
	switch ($input->getAttribute('name')) {
	case 'Email': $toreturn .= 'Email=' . urlencode($GLOBALS['email']) . '&'; break;
	case 'Passwd':$toreturn .= 'Passwd=' . urlencode($GLOBALS['pass']) . '&'; break;
	default: $toreturn .= $input->getAttribute('name') . '=' . urlencode($input->getAttribute('value')) . '&';
	}
    }
    // return array (string postdata, string postaction)
    return array(tidy($toreturn), $doc->getElementsByTagName('form')->item(0)->getAttribute('action'));
}

// POST login: https://accounts.google.com/ServiceLoginAuth
function login($postdata) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, $postdata[1]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata[0]);
    $buf = curl_exec($ch); #this is not the g+ home page, because the b**** doesn't redirect properly
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
    echo "\n[+] Sending POST request to: " . $postdata[1] . "\n\n";
}

// GET status update form: Parse the webpage and collect form data
function update_profile_status() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, 'https://m.google.com/app/plus/?v=compose&group=m1c&hideloc=1');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $buf = utf8_decode(html_entity_decode(str_replace('&', '', curl_exec($ch))));
    $header = curl_getinfo($ch);
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
    $params = '';
    $doc = new DOMDocument;
    $doc->loadxml($buf);
    $inputs = $doc->getElementsByTagName('input');
    foreach ($inputs as $input) {
	    if (($input->getAttribute('name') != 'editcircles')) {
		$params .= $input->getAttribute('name') . '=' . urlencode($input->getAttribute('value')) . '&';
	    }
    }
    $params .= 'newcontent=' . urlencode($GLOBALS['status']);
    //$baseurl = $doc->getElementsByTagName('base')->item(0)->getAttribute('href');
    $baseurl = 'https://m.google.com' . parse_url($header['url'], PHP_URL_PATH);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_URL, $baseurl . '?v=compose&group=m1c&hideloc=1&a=post');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_REFERER, $baseurl . '?v=compose&group=m1c&hideloc=1');
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    $buf = curl_exec($ch);
    $header = curl_getinfo($ch);
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
    echo "\n[+] POST Updating status on: " . $baseurl . "\n\n";
}

// Not implemented yet! just ignore this function for now
function update_page_status() {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['pc_uagent']);
    curl_setopt($ch, CURLOPT_URL, 'https://plus.google.com/u/0/b/' . $GLOBALS['pageid'] . '/');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    $buf = utf8_decode(html_entity_decode(str_replace('&', '', curl_exec($ch))));
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
}

// GET logout: Just logout to look more human like and reset cookie :)
function logout() { 
    echo "\n[+] GET Logging out: \n\n";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_COOKIEJAR, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $GLOBALS['cookies']);
    curl_setopt($ch, CURLOPT_USERAGENT, $GLOBALS['uagent']);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_URL, 'https://www.google.com/m/logout');
    $buf = curl_exec($ch);
    curl_close($ch);
    if ($GLOBALS['debug']) {
	echo $buf;
    }
}

// Convert links from t.co/xxxx to full links
function untiny($short_url) {
        $response = @simplexml_load_file('http://untiny.me/api/1.0/extract/?url='.$short_url);
        $result = $response->org_url;
        if(strstr($result, 'http://'))
                return untiny($result);
        else
                return $short_url;
}

function untiny_message($message) {
        if(strstr($message, 'http://')) {
                $i = 10;
                while(preg_match("#http:\/\/t\.co\/(\w+)#",$message, $regex) && $i--) {
                        $short_url = $regex[0];
                        $message = preg_replace("#http:\/\/t\.co\/(\w+)#", untiny($short_url), $message, 1);
                }
        }
        return $message;
}

function tidy($str) {
    return rtrim($str, "&");
}

?>
