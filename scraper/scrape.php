<?PHP
function createpostdata($icsid, $icstatenum, $icaction){
    return 'ICType=Panel&ICXPos=0&ICYPos=0&ResponsetoDiffFrame=-1&TargetFrameName=None&GSrchRaUrl=None&FacetPath=None&ICFocus=&ICSaveWarningFilter=0&ICChanged=-1&ICResubmit=0&ICActionPrompt=false&ICTypeAheadID=&ICFind=&ICAddCount=&ICElementNum=0&ICNAVTYPEDROPDOWN=0&ICSID='.$icsid.'&ICStateNum='.icstatenum.'&ICAction='.$icaction;
}

// Na-na-na come on...
$curlhandle = curl_init();

// Set up cookies n shit
curl_setopt($curlhandle, CURLOPT_URL, $LOGINURL);
curl_exec($curlhandle);

// Actually connect to SIMS
curl_setopt($curlhandle, CURLOPT_URL, $SIMSURL);
curl_setopt($curlhandle, CURLOPT_RETURNTRANSFER, TRUE);
$html = curl_exec($curlhandle);

// Use DOM Parser and grab form data
$html = str_get_html($html);
$icsid = $html->find('input[id=ICSID]')->value;
$icstatenum = $html->find('input[id=ICStateNum]')->value;

$postdata = createpostdata($icsid, $icstatenum+1, 'DERIVED_SSS_BCC_SSR_EXPAND_COLLAPS$0'); // Open first dropdown menu

curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
curl_exec($curlhandle);

$postdata = createpostdata($icsid, $icstatenum+1, 'CRSE_TITLE$0'); // Select first course
curl_setopt($curlhandle, CURLOPT_POSTFIELDS, $postdata);
curl_exec($curlhandle);

$postdata = createpostdata($icsid, $icstatenum+1, 'DERIVED_SAA_CRS_SSR_PB_GO'); // Select current term
curl_setopt($curlhande, CURLOPT_POSTFIELDS, $postdata);
curl_exec($curlhandle);

$postdata = createpostdata($icsid, $icstatenum+1, 'DERIVED_SAA_CRS_SSR_PB_GO&DERIVED_SAA_CRS_TERM_ALT=1151'); // 1YYS|YY = last 2 digits of year|S = semester (1,4,7 => spr, sum, fall)
curl_setopt($curlhande, CURLOPT_POSTFIELDS, $postdata);
curl_exec($curlhandle);

$postdata = createpostdata($icsid, $icstatenum+1, 'CLASS_TBL_VW5$fdown$0&DERIVED_SAA_CRS_TERM_ALT=1151');
curl_setopt($curlhande, CURLOPT_POSTFIELDS, $postdata);
curl_exec($curlhandle);

curl_close($curlhandle);