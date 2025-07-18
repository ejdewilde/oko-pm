<?php

/*
 * OKO procesmonitor voor Wordpress
 * @package      oko-pm
 * @link         https://www.hansei.nl/plugins/oko-pm/
 * @author       Erik Jan de Wilde <ej@hansei.nl>
 * @copyright    2021 Erik Jan de Wilde
 * @license      GPL v2 or later
 * Plugin Name:  OKO procesmonitor
 * Description:  Visuals for Oprgroeien in een Kansrijke Omgeving: de procestool. This plugin depends on Fluent CRM to be installed and active.
 * Version:      1.8.1
 * Plugin URI:   https://www.hansei.nl/plugins
 * Author:       Erik Jan de Wilde, (c) 2024, HanSei
 * Text Domain:  oko-pm
 * Domain Path:  /languages/
 * Network:      true
 * Requires PHP: 7.4
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * version 1.3: nieuwe figuur
 * version 1.4: inspiratiekaart toevoegingen
 */

// first make sure this file is called as part of WP
defined('ABSPATH') or die('Hej då');

ini_set('display_errors', 'Off');

$plugin_root = substr(plugin_dir_path(__FILE__), 0, -5) . "/";
add_action('plugins_loaded', 'oko_pm_laad_updater');

function oko_pm_laad_updater()
{
    $pad = plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';

    if (file_exists($pad)) {
        require_once $pad;

        // Gebruik v5 factory
        if (class_exists('\YahnisElsts\PluginUpdateChecker\v5\PucFactory')) {
            $updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker('https://github.com/ejdewilde/oko-pm/', __FILE__, 'oko-pm');

            $updateChecker->setBranch('main');
            $updateChecker->getVcsApi()->enableReleaseAssets();
        } else {
            error_log('PucFactory class niet gevonden.');
        }
    } else {
        error_log('plugin-update-checker.php niet gevonden op: ' . $pad);
    }
}

function oko_pm_shortcode()
{
    include_once "oko-pm.php";
    $wat = new OKO_pm();

    $ta = $wat->get_interface();
}
function pm_overzicht_shortcode()
{

    include_once "oko-security.php";
    $ef = new OKO_check();
    $ok = $ef->check_het();
    //echo $ok;
    if ($ok) {
        include_once "pm_overzicht.php";
        $wat = new PM_overzicht();
        $ta  = $wat->get_interface();
    }
}

function oko_pm_register_shortcode()
{
    add_shortcode('show-oko-pm', 'oko_pm_shortcode');
    add_shortcode('show-pm-overzicht', 'pm_overzicht_shortcode');
}

add_action('init', 'oko_pm_register_shortcode');
