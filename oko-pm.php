<?php
/*
OKO procesmonitor v2
 */

ini_set('display_errors', 'Off');
//echo "oja";

function ts($test)
{ // for debug/development only
    echo '<pre>';
    //echo 'tijdelijke testgegevens:';
    echo print_r($test, true);
    echo '</pre>';
}

function schoon($input)
{
    return iconv('UTF-8', 'ASCII//TRANSLIT', $input);
}

class OKO_pm
{
    public $gebruiker_id;
    public $user_name;
    public $gem = [];
    public $plugindir;
    public $url;
    public $start_tekst;
    public $faseteksten = [];
    public $stapteksten = [];
    public $staptitels  = [];
    public $staptotalen = [];
    public $scores      = [];
    public $tipstring;
    public $statusstring;

    public function __construct()
    {
        //include_once "interfaceDB.php";

        if ($_SERVER['HTTP_HOST'] == 'localhost') {
                               //echo 'ja lokaal';
            $this->lok = true; // voor de test
        } else {
            $this->lok = false;
        }

        $this->gebruiker_id = get_current_user_id();
        $current_user       = wp_get_current_user();
        $this->user_name    = $current_user->data->display_name;
        //ts($current_user->data);

        $this->gem = $this->haal_gemeente($current_user->data->user_email);

        if ($current_user->data->user_email == 'nikita.vantaarling@hotmail.com') {
            $this->gem["naam"] = 'Noord-Veluwe HEP';
            $this->gem["id"]   = 23;
        }

        if ($this->lok) {
            $this->gem['id']   = 90;             // voor de test
            $this->gem['naam'] = 'Testgemeente'; // voor de test
        }

        //ts($this->gem);
        $this->plugindir   = plugin_dir_url(__FILE__);
        $this->url         = $_SERVER['HTTP_HOST'];
        $this->start_tekst = $this->maak_start_tekst();
        $this->maak_fase_stap_teksten();
        $this->staptotalen  = $this->haal_max_score_per_stap();
        $this->scores       = $this->maak_scores();
        $this->tipstring    = $this->haal_tipstring();
        $this->statusstring = $this->maakstatusstring();
    }
    public function get_plaatje_url($post_id)
    {
        $zz = "select p.guid from
        (select meta_value from wp_postmeta where post_id = " & $post_id & " and meta_key = '_thumbnail_id') a
        inner join wp_posts p on a.meta_value = p.id";
        $result = $this->poke_wpdb($zz, 'get_results');
        return $result[0]->guid;
    }

    public function maak_scores()
    {
        return isset($this->gem['id']) ? $this->haal_scores($this->gem['id']) : null;
    }

    public function maak_fase_stap_teksten()
    {
        $this->staptitels = $this->haal_stap_titels();
        $fasenstappen     = $this->haal_fasen_stappen();

        foreach ($fasenstappen as $soort => $inhs) {
            if ($soort == 1) {
                ksort($inhs);
                foreach ($inhs as $ind => $txt) {
                    $this->faseteksten[] = "<h3 class='tiptitel'>fase $ind</h3><p class='tiptekst'>$txt</p>";
                }
            } elseif ($soort == 2) {
                ksort($inhs);
                foreach ($inhs as $ind => $txt) {
                    $this->stapteksten[] = "<p class='tiptekst'>$txt</p>";
                }
            }
        }
    }

    public function maakstatusstring()
    {
        $curus = isset($this->gem['id']) ? $this->haal_laatste_score($this->gem['id']) : null;
        //ts($curus);
        //ts($this->gem);

        if (! isset($this->gem['naam'])) {
            return '<div class="status">Niemand ingelogd die voor deze gemeente de procesmonitor kan gebruiken</div>';
        }

        $stat = '<div class="status">Procesmonitor van <b>' . $this->gem["naam"] . '</b>';

        if ($curus) {
            $uid       = $curus["user_id"];
            $user_info = get_userdata($uid);
            $timst     = $curus["archiefUniX"];
            $naam      = $user_info->data->display_name;
            $dag       = date("j M Y", $timst);
            $stat .= '. Laatst bijgewerkt door ' . $naam . ' op ' . $dag . '</div>';
        } else {
            $stat .= '. Nog geen invoer geweest voor deze gemeente</div>';
        }

        return $stat;
    }

    public function get_interface()
    {

        $output = $this->get_style();
        $output .= $this->statusstring;
        $output .= '

                <div class="hoofd">

                    <div class="con1" id="container1"></div>

                    <div class="con2" id="container2"></div>
                    <div class="staptitel" id="staptitel"></div>
                    <div class="intro" id="intro">
                        <h3 class="tiptitel">Welkom bij de OKO procesmonitor!</h3>
                        <p class="tiptekst">Beweeg met je muis over de fasen van OKO en selecteer een stap boven door er op één te klikken...</p></div>

                    <div class="item">
                        <form id="zelfscan" autocomplete="off" action="#" method="POST">
                            <div id="kop"></div>
                            <div id="vraag"></div>
                            <div id="items"></div>
                            <div id="starttekst">' . schoon($this->start_tekst) . '</div>
                        </form>
                    </div>
                    <div class="tip" id="tip"><p></p></div>
                    <div class="faseinfo" id="faseinfo"></div>

                </div>';
        $output .= '<footer>';

        $output .= $this->get_biebs();
        $output .= '</footer>';
        echo $output;
        return;
    }

    public function maak_start_tekst()
    {
        return "<h2>Hoe gebruik je deze Procestool?</h2>
            <p>
            <ul class='tiplijst'>
            <li>Beweeg je muis over de stappen (boven) en de thema's (links) om meer informatie daarover te zien.</li>
            <li>Wil je aan de slag, selecteer dan een stap of thema.</li>
            <li>Geef bij elke stap aan welke acties je al ondernomen hebt, en bekijk de tips voor de stappen die je nog gaat zetten. Als je je muis over een actie beweegt, verschijnt de tip vanzelf.</li>
            </ul>
            </p>";
    }

    public function get_style()
    {
        wp_enqueue_style('okpmd3', $this->plugindir . 'css/oko-pm.css');
    }

    public function get_biebs()
    {
        $output = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>';
        //$output = '<script src="https://code.jquery.com/jquery-3.7.1.slim.min.js" integrity="sha256-kmHvs0B+OpCW5GVHUNjv9rOmY0IvSIRcf7zGUDTDQM8=" crossorigin="anonymous"></script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var gemeente =' . json_encode($this->gem) . '</script>';
        $output .= '<script type="text/JavaScript">var uid=' . $this->gebruiker_id . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var staptitels =' . schoon(json_encode($this->staptitels)) . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var tipstring =' . schoon(json_encode($this->tipstring, JSON_INVALID_UTF8_SUBSTITUTE)) . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var stapteksten =' . schoon(json_encode($this->stapteksten)) . '</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">var faseteksten =' . schoon(json_encode($this->faseteksten)) . '</script>';
        $output .= '<script type="text/JavaScript">var staptotalen=' . json_encode($this->staptotalen) . '</script>';
        $output .= '<script type="text/JavaScript">var scores=' . json_encode($this->scores) . '</script>';
        $output .= "<script src='https://d3js.org/d3.v6.min.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/d3.tip.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/oko-pm.js' type='text/javascript'  charset='iso-8859-1'></script>";

        return $output;
    }
    public function poke_wpdb($sql, $methode)
    {
        global $wpdb;
        //ts($sql);
        $zql = $wpdb->prepare($sql, "");
        switch ($methode) {
            case 'get_results':
                $aa = $wpdb->get_results($zql, ARRAY_A);
                break;
            case 'get_row':
                $aa = $wpdb->get_row($zql, ARRAY_A);
                break;
            case 'get_var':
                $aa = $wpdb->get_var($zql);
                break;
            case 'query':
                $aa = $wpdb->query($zql);
                break;
        }
        return $aa;
    }
    public function haal_vinkjes($gid)
    {

        $sql     = "SELECT meta_value AS result FROM tool_usermeta WHERE gem_id = " . $gid . " ORDER BY umeta_id DESC LIMIT 1";
        $data    = $this->poke_wpdb($sql, "get_var");
        $gevinkt = [];
        if (! empty($data) && isset($data['result'])) {
            $results = json_decode($data['result'], true);
            foreach ($results as $key => $val) { // code line 25, interfaceDB.php
                if (substr($key, 0, 2) == 'cb') {
                    $gevinkt[] = $key;
                }
            }
        }
        return $gevinkt;
    }

    public function kweer($stap)
    {
        $sql = "select ID, stap, volgorde, vooraf_text, item, tip from
            (select ID, post_title as item, post_content as tip from wp_posts where post_type = 'check' and post_status = 'publish') t
            left join
            (select post_id, meta_value as volgorde, meta_key from wp_postmeta where meta_key = 'volgorde') h on t.ID=h.post_id
            left join
            (select post_id, meta_value as stap, meta_key from wp_postmeta where meta_key = 'stap') a on t.ID=a.post_id
            left join
            (select post_id, meta_value as vooraf_text, meta_key from wp_postmeta where meta_key = 'vooraf_text') v on t.ID=v.post_id
            where stap = " . $stap . ";";
        $data = $this->poke_wpdb($sql, "get_results");
        return $data;
    }

    public function haal_scores($gem)
    {
        $sql         = "SELECT meta_value AS result FROM tool_usermeta WHERE gem_id = " . $gem . " ORDER BY umeta_id DESC LIMIT 1;";
        $result_json = $this->poke_wpdb($sql, "get_var");
        $score       = array_fill(1, 10, 0);
        if (! empty($result_json)) {
            $results = json_decode($result_json, true);
            foreach ($results as $key => $val) {
                if (substr($key, 0, 2) == 'cb') {
                    $stap = intval(substr($key, 2));
                    if (isset($score[$stap])) {
                        $score[$stap]++;
                    }
                }
            }
        }
        return $score;
    }

    public function haal_laatste_score($gid)
    {
        $sql = "SELECT * FROM tool_usermeta WHERE gem_id = " . $gid . " AND archiefUnix IS NOT NULL ORDER BY umeta_id DESC LIMIT 1";
        //ts($sql);
        $data = $this->poke_wpdb($sql, "get_row");
        return $data;
    }

    public function haal_max_score_per_stap()
    {

        $sql = "SELECT CAST(a.meta_value AS UNSIGNED) AS stap, COUNT(t.ID) AS maks
            FROM wp_posts t
            LEFT JOIN wp_postmeta h ON t.ID = h.post_id AND h.meta_key = 'volgorde'
            LEFT JOIN wp_postmeta a ON t.ID = a.post_id AND a.meta_key = 'stap'
            LEFT JOIN wp_postmeta v ON t.ID = v.post_id AND v.meta_key = 'vooraf_text'
            WHERE t.post_type = 'check' AND t.post_status = 'publish'
            GROUP BY a.meta_value
            ORDER BY stap ASC";

        $results = $this->poke_wpdb($sql, "get_results");
        $scores  = [];
        foreach ($results as $row) {
            $scores[$row['stap']] = (int) $row['maks'];
        }
        return $scores;
    }

    public function haal_tipstring()
    {
        $sql = "
            SELECT b.ID, b.post_content AS tip, a.meta_value AS stap
            FROM wp_posts b
            LEFT JOIN wp_postmeta a ON b.ID = a.post_id AND a.meta_key = 'stap'
            WHERE b.post_type = 'check' AND b.post_status = 'publish'
        ";
        $results = $this->poke_wpdb($sql, "get_results");
        $tips    = [];
        foreach ($results as $row) {
            $tips['cb' . $row['stap'] . '_' . $row['ID']] = $row['tip'];
        }
        return $tips;
    }

    public function haal_gemeente_gebruiker($uid)
    {
        $sql = "SELECT g.id, g.naam
            FROM oko_gemeenten g
            INNER JOIN wp_bp_groups b ON b.id = g.bp_id
            INNER JOIN wp_bp_groups_members m ON m.group_id = b.id
            INNER JOIN wp_users w ON w.ID = m.user_id
            WHERE w.ID = " . $uid . ";)";
        $results = $this->poke_wpdb($sql, "get_results");
        foreach ($results as $row) {
            if ($row['naam'] == 'OKO team') {
                return $row;
            }
        }
        return ! empty($results) ? end($results) : false;
    }

    public function haal_gemeente($eml)
    {
        $sql = "SELECT g.naam, g.id
            FROM wp_fc_subscribers s
            INNER JOIN wp_fc_subscriber_pivot p ON s.id = p.subscriber_id
            INNER JOIN wp_fc_tags t ON p.object_id = t.id
            INNER JOIN oko_gemeenten g ON g.fc_id = t.id
            WHERE s.email = '" . $eml . "';";
        $results = $this->poke_wpdb($sql, "get_row");

        return $results;
    }

    public function haal_stap_titels()
    {
        global $wpdb;
        $sql     = "SELECT * FROM oko_stappen ORDER BY id;";
        $results = $this->poke_wpdb($sql, "get_results");
        $titels  = [];
        foreach ($results as $row) {
            $titels[$row['id']] = $row['tekst'];
        }
        return $titels;
    }

    public function haal_fasen_stappen()
    {
        $sql          = "SELECT post_title AS kop, post_content AS inhoud FROM wp_posts WHERE post_type = 'stap' AND post_status = 'publish' ORDER BY post_title;";
        $results      = $this->poke_wpdb($sql, "get_results");
        $fasenStappen = [];
        foreach ($results as $row) {
            $nummer = substr($row["kop"], 5);
            if (strpos($row["kop"], "ase") > 0) {
                $fasenStappen[1][$nummer] = $row["inhoud"];
            } elseif (strpos($row["kop"], "ommu") > 0) {
                $fasenStappen[1][5] = $row["inhoud"];
            } else {
                $fasenStappen[2][$nummer] = $row["inhoud"];
            }
        }
        return $fasenStappen;
    }
}
