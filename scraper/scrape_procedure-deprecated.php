<?PHP
$LOGINURL = 'https://go.sfu.ca/psp/paprd/EMPLOYEE/EMPL/h/?tab=PAPP_GUEST';
$SIMSURL = 'https://sims-prd.sfu.ca/psc/csprd/EMPLOYEE/HRMS/c/COMMUNITY_ACCESS.SSS_BROWSE_CATLG.GBL';
$USERAGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36';
$COOKIEJAR = 'cookies.txt';
$HEADERS = array('Connection: keep-alive', 'Origin: https://sims-prd.sfu.ca');

function createpostdata($icsid, $icstatenum, $icaction){
	return 'ICAJAX=1&ICNAVTYPEDROPDOWN=0&ICType=Panel&ICElementNum=0&ICStateNum='.$icstatenum.'&ICAction='.$icaction.'&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICSID='.$icsid.'&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=';
}

unlink($COOKIEJAR);

// Na-na-na come on...
$curlhandle = curl_init();

// Set up cookies n shit
curl_setopt($curlhandle, CURLOPT_URL, $LOGINURL);
curl_setopt($curlhandle, CURLOPT_USERAGENT, $USERAGENT);
curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curlhandle, CURLINFO_HEADER_OUT, TRUE);
//curl_setopt($curlhandle, CURLOPT_HEADER, TRUE);
curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($curlhandle, CURLOPT_COOKIEJAR, $COOKIEJAR);
curl_setopt($curlhandle, CURLOPT_COOKIEFILE, $COOKIEJAR);
$o = curl_exec($curlhandle);
$fh = fopen('o.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);

// Actually connect to SIMS
curl_setopt($curlhandle, CURLOPT_URL, $SIMSURL);
curl_setopt($curlhandle, CURLOPT_HTTPHEADER, $HEADERS);
$html = curl_exec($curlhandle);
$fh = fopen('o0.txt', 'w');
fwrite($fh, $html);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);

// Use DOM Parser and grab form data	
$doc = new DOMDocument;
@$doc->loadHTML($html);
$icsid = $doc->getElementById('ICSID')->getAttribute('value');
$icstatenum = $doc->getElementById('ICStateNum')->getAttribute('value');

echo $icsid.' '.$icstatenum.'<br />';
$icsid = urlencode($icsid);
$icstatenum = urlencode($icstatenum);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('DERIVED_SSS_BCC_SSR_EXPAND_COLLAPS$0')); // Open first dropdown menu

curl_setopt($curlhandle, CURLOPT_POST, TRUE);
curl_setopt($curlhandle, CURLOPT_REFERER, $SIMSURL);
curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, FALSE);
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o1.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('CRSE_TITLE$0')); // Select first course
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o2.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('DERIVED_SAA_CRS_SSR_PB_GO')); // Select current term if possible, else select first possible term
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o3.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('DERIVED_SAA_CRS_SSR_PB_GO&DERIVED_SAA_CRS_TERM_ALT=1151')); // 1YYS|YY = last 2 digits of year|S = semester (1,4,7 => spr, sum, fall)
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o4.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

$postdata = createpostdata($icsid, $icstatenum++, urlencode('CLASS_TBL_VW5$fviewall$0&DERIVED_SAA_CRS_TERM_ALT=1151')); //View all
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o5.txt', 'w');
fwrite($fh, $o);
$o = curl_getinfo($curlhandle, CURLINFO_HEADER_OUT);
fwrite($fh, $o);
var_dump($postdata);

//Search for: CLASS_SECTION$0 (Section code, e.g. D100-LEC (1235))
// CLASS_MTGPAT$scroll$0 (Secion table header)
// PSEDITBOX_DISPONLY (Day Start End Dates)
// PSLONGEDITBOX (Room Instructor)
// win0divCLASS_STATUS$0 -> div -> img -> alt=Open/Closed
// PSLEVEL1GRIDNBO (Counter var for sections, divide by 2)
// DERIVED_SSS_BCC_SSR_ALPHANUM_A
// DERIVED_SAA_CRS_RETURN_PB$167$ (ret to browse) DERIVED_SAA_CRS_RETURN_PB

curl_close($curlhandle);