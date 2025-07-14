<?php
/*
OKO procesmonitor overzichtpagina
 */

ini_set('display_errors', 'On');
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

class PM_overzicht
{
    public function __construct()
    {
        $this->data          = $this->kweer();
        $this->maxes         = $this->haal_max_score_per_stap();
        $this->staptitels    = $this->haal_stap_titels();
        $this->check_teksten = $this->haal_check_teksten();
        //ts(json_encode($this->check_teksten, JSON_PRETTY_PRINT));
        //ts(json_encode($this->data));
        $this->plugindir = plugin_dir_url(__FILE__);
        //exit;
    }

    public function get_interface()
    {
        $output = '';
        //header('Content-Type: application/json; charset=utf-8');

        $output .= $this->get_style();

        $output .= '
<h1>Voortgang OKO per gemeente</h1>
<div id="container">
    <div id="main">
      <div id="visualisatie"></div>
      <div class="tooltip" id="tooltip"></div>
    </div>
    <div id="details">
        <div id="filterSticky">
            <label for="filterJaar"><strong>
                Filter op startjaar:</strong>
            </label>
            <select id="filterJaar">
                <option value="">Alle jaren</option>
            </select>
            <hr>
        </div>
        <div id="metaInfo" class="meta-info">
            <p><strong>Laatst bijgewerkt door:</strong> <span id="invullerNaam">–</span></p>
            <p><strong>Datum:</strong> <span id="invulDatum">–</span></p>
        </div>
        <div id="detailContent">
            Klik op een vakje om meer informatie te tonen.
        </div>
    </div>
</div>';

        $output .= $this->get_biebs();

        echo $output;

        return;
    }

    public function get_style()
    {
        wp_enqueue_style('okpmd3', $this->plugindir . 'css/pm-overzicht.css');
    }

    public function get_biebs()
    {
        $output = '<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">const gemeentenData =[' . json_encode($this->data, JSON_UNESCAPED_UNICODE) . '];</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">const maxScores=' . json_encode($this->maxes) . ';</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">const stepNames =' . json_encode($this->staptitels, JSON_UNESCAPED_UNICODE) . ';</script>';
        $output .= '<script type="text/JavaScript" charset="iso-8859-1">const itemlabels = [' . json_encode($this->check_teksten, JSON_UNESCAPED_UNICODE) . '];</script>';
        $output .= "<script src='https://d3js.org/d3.v7.min.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/d3.tip.js' type='text/javascript'></script>";
        $output .= "<script src='" . $this->plugindir . "js/pm-overzicht.js' type='text/javascript'  charset='iso-8859-1'></script>";

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

    public function kweer()
    {
        $sql = "SELECT
  g.naam,
  g.id AS gem_id,
  g.startjaar,
  w.display_name AS invuller,
  FROM_UNIXTIME(t.archiefUnix) AS datum,
  t.meta_value AS checks
FROM oko_gemeenten g
JOIN (
    SELECT gem_id, MAX(archiefUnix) AS laatste
    FROM tool_usermeta
    GROUP BY gem_id
) AS laatste_invoer ON g.id = laatste_invoer.gem_id
JOIN tool_usermeta t ON t.gem_id = laatste_invoer.gem_id AND t.archiefUnix = laatste_invoer.laatste
LEFT JOIN wp_users w ON t.user_id = w.ID
WHERE g.naam <> 'testgemeente'
";

        $data = $this->poke_wpdb($sql, "get_results");
        ts($sql);
        ts($data);
        $terug = [];
        foreach ($data as $row) {
            $terug[$row["gem_id"]]["gem_id"]   = $row["gem_id"];
            $terug[$row["gem_id"]]["naam"]     = $row["naam"];
            $terug[$row["gem_id"]]["sinds"]    = $row["startjaar"];
            $terug[$row["gem_id"]]["checks"]   = $this->select_checks($row["checks"]);
            $terug[$row["gem_id"]]["scores"]   = $this->maak_scores($row["checks"]);
            $terug[$row["gem_id"]]["invuller"] = $row["invuller"];
            $terug[$row["gem_id"]]["datum"]    = $row["datum"];

        }
        return $terug;
    }

    public function maak_scores($result_json)
    {
        $score   = array_fill(1, 10, 0);
        $results = json_decode($result_json, true);
//ts($results);
        foreach ($results as $key => $val) {
            if (substr($key, 0, 2) == 'cb') {
                $stap = intval(substr($key, 2));
                $score[$stap]++;
            }
        }
//ts($score);
// tel de scores per stap op
        return $score;
    }

    public function select_checks($result_json)
    {
        $results = json_decode($result_json, true);
//ts($results);
        $terug = [];
        foreach ($results as $key => $val) {
            if (substr($key, 0, 2) == 'cb') {
                $terug[] = $key;
            }
        }
//ts($score);
// tel de scores per stap op
        return $terug;
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
    public function haal_check_teksten()
    {
        $sql = "
SELECT b.ID, b.post_title AS tsjek, a.meta_value AS stap
FROM wp_posts b
LEFT JOIN wp_postmeta a ON b.ID = a.post_id AND a.meta_key = 'stap'
WHERE b.post_type = 'check' AND b.post_status = 'publish'
";
        $results = $this->poke_wpdb($sql, "get_results");
        $terug   = [];
        foreach ($results as $row) {
            $terug['cb' . $row['stap'] . '_' . $row['ID']] = $row['tsjek'];
        }
        return $terug;
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
        $sql = "SELECT post_title AS kop, post_content AS inhoud FROM wp_posts WHERE post_type = 'stap' AND post_status =
'publish' ORDER BY post_title;";
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
