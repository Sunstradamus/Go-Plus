<?PHP
$LOGINURL = 'https://go.sfu.ca/psp/paprd/EMPLOYEE/EMPL/h/?tab=PAPP_GUEST';
$SIMSURL = 'https://sims-prd.sfu.ca/psc/csprd/EMPLOYEE/HRMS/c/COMMUNITY_ACCESS.SSS_BROWSE_CATLG.GBL';
$USERAGENT = 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/32.0.1700.76 Safari/537.36';

function createpostdata($icsid, $icstatenum, $icaction){
    return 'ICType=Panel&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=&ICElementNum=0&ICNAVTYPEDROPDOWN=0&ICSID='.$icsid.'&ICStateNum='.icstatenum.'&ICAction='.$icaction;
}

unlink('cookies.txt');

// Na-na-na come on...
$curlhandle = curl_init();

// Set up cookies n shit
curl_setopt($curlhandle, CURLOPT_URL, $LOGINURL);
curl_setopt($curlhandle, CURLOPT_USERAGENT, $USERAGENT);
curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($curlhandle, CURLOPT_FOLLOWLOCATION, TRUE);
curl_setopt($curlhandle, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($curlhandle, CURLOPT_COOKIEFILE, 'cookies.txt');
$o = curl_exec($curlhandle);
$fh = fopen('o.txt', 'w');
fwrite($fh, $o);

// Actually connect to SIMS
curl_setopt($curlhandle, CURLOPT_URL, $SIMSURL);
$html = curl_exec($curlhandle);
$fh = fopen('o0.txt', 'w');
fwrite($fh, $html);

// Use DOM Parser and grab form data	
$doc = new DOMDocument;
$doc->loadHTML($html);
$icsid = $doc->getElementById('ICSID')->getAttribute('value');
$icstatenum = $doc->getElementById('ICStateNum')->getAttribute('value');

echo $icsid.$icstatenum.'<br />';

$postdata = createpostdata($icsid, $icstatenum, 'DERIVED_SSS_BCC_SSR_EXPAND_COLLAPS$0'); // Open first dropdown menu

echo $postdata;

curl_setopt($curlhandle, CURLOPT_POST, TRUE);
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o1.txt', 'w');
fwrite($fh, $o);

$postdata = createpostdata($icsid, $icstatenum, 'CRSE_TITLE$0'); // Select first course
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o2.txt', 'w');
fwrite($fh, $o);

$postdata = createpostdata($icsid, $icstatenum, 'DERIVED_SAA_CRS_SSR_PB_GO'); // Select current term
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o3.txt', 'w');
fwrite($fh, $o);

$postdata = createpostdata($icsid, $icstatenum, 'DERIVED_SAA_CRS_SSR_PB_GO&DERIVED_SAA_CRS_TERM_ALT=1151'); // 1YYS|YY = last 2 digits of year|S = semester (1,4,7 => spr, sum, fall)
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o4.txt', 'w');
fwrite($fh, $o);

$postdata = createpostdata($icsid, $icstatenum, 'CLASS_TBL_VW5$fdown$0&DERIVED_SAA_CRS_TERM_ALT=1151');
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
$o = curl_exec($curlhandle);
$fh = fopen('o5.txt', 'w');
fwrite($fh, $o);

curl_close($curlhandle);