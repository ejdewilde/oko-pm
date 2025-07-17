<?php
/*
OKO procesmonitor v2
 */

ini_set('display_errors', 'On');
//echo "oja";
function tsc($test)
{ // for debug/development only
    echo '<pre>';
    //echo 'tijdelijke testgegevens:';
    echo print_r($test, true);
    echo '</pre>';
}
class OKO_check
{
    public function check_het()
    {
        $output = "<style>
            .alert {
                color: red;
                border-radius: 5px;
                border-color: red;
                border-width: 2px;
                padding: 1em;
            }
            </style>";
        if ($_SERVER['HTTP_HOST'] == 'localhost') {
                               //echo 'ja lokaal';
            $this->lok = true; // voor de test
        } else {
            $this->lok = false;
        }

        $this->gebruiker_id = get_current_user_id();
        if ($this->gebruiker_id) {
            $this->okoteam = $this->haal_okoteam();
            //tsc($this->okoteam);
            $current_user    = wp_get_current_user();
            $this->user_name = $current_user->data->display_name;
        } else {
            $output .= '<div class="alert alert-danger" role="alert">';
            $output .= '<p>Je bent niet ingelogd op het OKO-platform...</p>';
            $output .= '<p>Neem eventueel contact op met de beheerder van de site.</p>';
            $output .= '</div>';
            echo $output;
            return false;
        }

        if ($this->lok) {
            $this->gem['id']   = 1;              // voor de test
            $this->gem['naam'] = 'Testgemeente'; // voor de test
        }

        if (! $this->check_okoteam()) {
            $output .= '<div class="alert alert-danger" role="alert">';
            $output .= '<p>Je bent geen lid van het OKO-team, je mag geen gebruik maken van deze voorziening...</p>';
            $output .= '<p>Neem eventueel contact op met de beheerder van de site.</p>';
            $output .= '</div>';
            echo $output;
            return false;
        }
        return true;
    }

    public function haal_okoteam()
    {
        $sql     = "SELECT user_id FROM `wp_bp_groups_members` WHERE `group_id` = '3';";
        $results = $this->poke_wpdb($sql, "get_results");
        $terug   = [];
        //tsc($results);
        foreach ($results as $row) {
            $terug[] = $row["user_id"];
        }
        return $terug;
    }

    public function check_okoteam()
    {
        //tsc($this->okoteam);
        //tsc($this->gebruiker_id);
        if (in_array($this->gebruiker_id, $this->okoteam)) {
            //echo 'ja';
            return true;
        } else {
            //echo 'nee';
            return false;
        }
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
}
