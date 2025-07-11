<?php

//include wpdb object
$path = preg_replace('/wp-content.*$/', '', __DIR__);
require_once $path . 'wp-load.php';

global $wpdb;

//for debug: https://acc.kansrijkeomgeving.nl/wp-content/plugins/oko-pm/getorstoredata.php?gid=2

function schoontxt($input)
{
    return $input;
}

function ts($test)
{ // for debug/development only
    echo '<pre>';
    echo print_r($test, true);
    echo '</pre>';
}

//ini_set('display_errors', 'On');

$gid = 0;
$uid = 0;

$aantalperstap = array();
for ($f = 1; $f < 11; $f++) {
    $aantalperstap[$f] = 0;
}

if ($_GET) 
{
    $gid = $_GET['gid'];
    
    ?>
    <link rel="stylesheet" href="https://acc.kansrijkeomgeving.nl/wp-content/plugins/oko-pm/css/oko-pm.css">
    <?php

   $uid = $_GET['uid'];
   $gid = $_GET['gid'];
   SlaFormOp($uid, $gid, json_encode($_GET));
}

if ($_POST) 
{
    if (key_exists("bewaar", $_POST)) {
        $uid = $_POST['uid'];
        $gid = $_POST['gid'];
        SlaFormOp($uid, $gid, json_encode($_POST));
    } elseif (key_exists("haalitems", $_POST)) {
        global $gid;
        $gid = $_POST['gid'];
        haal_items($gid);

    } elseif (key_exists("haalscores", $_POST)) {
        global $lid, $tid;
        $lid = $_POST['gid'];
        haal_scores_en_vinkjes($gid);
    }
}

function haal_items($gem)
{
    global $aantalperstap;
    $resultaat = haal_vinkjes($gem);
    $output = '';
    
    for ($s = 1; $s < 11; $s++) {
        $output .= maak_cb_stap($s, $resultaat);
    }
    echo $output;
    exit;
}


function haal_vinkjes($gid)
{
    global $wpdb;
    $sql = $wpdb->prepare("SELECT meta_value AS result FROM tool_usermeta WHERE gem_id = %d ORDER BY umeta_id DESC LIMIT 1", $gid);  
    $data = $wpdb->get_row($sql, ARRAY_A);
    $gevinkt = [];
    if (!empty($data) && isset($data['result'])) {
        $results = json_decode($data['result'], true);
        foreach ($results as $key => $val) { // code line 25, interfaceDB.php
            if (substr($key, 0, 2) == 'cb') {
                $gevinkt[] = $key;
            }
        }
    }
    return $gevinkt;
}


function maak_cb_stap($stap, $scor)
{
    $dats = kweer($stap);
    $sam = array();
    $output = '<div id = "stap_' . $stap . '">';

    $voor = '';
    if ($dats) {
        foreach ($dats as $row) {

            $kie = 'cb' . $stap . '_' . $row["ID"];
            if ($row['vooraf_text'] != $voor) {
                $voor = $row['vooraf_text'];
                $output .= '<div class = "cbvooraf">' . schoontxt($voor) . '</div>';
            }
            $tsjek = '&nbsp;';
            if ($scor) {
                if (in_array($kie, $scor)) {
                    //echo 'zit er in!: ' . $kie . '</br>';
                    $tsjek = 'checked';
                }
            }
            $iid = "'" . $kie . "'";

            $output .= '<div class = "itemverz" onmouseover="tip(' . $iid . ')" onmouseout="ontip(' . $iid . ')"><span class= "blokkie"><input id=' . $iid . ' ' . $tsjek . ' onClick="SlaOp(' . $iid . ');" class="regular-checkbox" name=' . $iid . ' type="checkbox"><label for=' . $iid . '>&nbsp;</label></span>
            <span id=' . $kie . ' class = "textblok">' . trim($row["item"]) . '</span></div>';
        }
    }
    $output .= '</div>';
    return $output;
}


function kweer($stap)
    {   
        global $wpdb;
        $sql = $wpdb->prepare("select ID, stap, volgorde, vooraf_text, item, tip from
            (select ID, post_title as item, post_content as tip from wp_posts where post_type = 'check' and post_status = 'publish') t
            left join
            (select post_id, meta_value as volgorde, meta_key from wp_postmeta where meta_key = 'volgorde') h on t.ID=h.post_id
            left join
            (select post_id, meta_value as stap, meta_key from wp_postmeta where meta_key = 'stap') a on t.ID=a.post_id
            left join 
            (select post_id, meta_value as vooraf_text, meta_key from wp_postmeta where meta_key = 'vooraf_text') v on t.ID=v.post_id 
            where stap = %d", $stap);
        $data = $wpdb->get_results($sql, ARRAY_A); 
        return $data;
     }

function SlaFormOp($gebruiker, $locatie, $res)
{
 	global $wpdb;
    $today = date("Y-m-d H:i:s");
    $arch = time();
    $zql = "insert into tool_usermeta (user_id, gem_id, meta_key, meta_value, tijdstip, archiefUnix) values (" . $gebruiker . ", " . $locatie . ", 'form', '" . $res . "', '" . $today . "', '" . $arch . "');";
    
    $sql = $wpdb->prepare($zql);
    $data = $wpdb->query($sql); 
}


function log_dit($geb)
{
    //$tz = 'Europe/Amsterdam';
    //$timestamp = time();
    //$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
    //$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
    //$nu = $dt->format('d.m.Y, H:i:s');
    //include_once "interfaceDB.php";
    //$itl = new PmDb();
    //$aa = "insert into logs (gebeurtenis, gebruiker, tijdstip) values ('" . $geb . "', '" . $gebruiker . "', '" . $nu . "');";
    //$itl->exesql($aa);
}

?>