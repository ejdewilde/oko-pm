<?php

/*
 * OKO procesmonitor voor Wordpress
 * @package      oko-pm
 * @link         https://www.hansei.nl/plugins/oko-pm/
 * @author       Erik Jan de Wilde <ej@hansei.nl>
 * @copyright    2021 Erik Jan de Wilde
 * @license      GPL v2 or later
 * Plugin Name:  OKO procesmonitor
 * Description:  Visuals voor OKO: procesmonitor. This plugin depends on Fluent CRM to be installed and active.
 * Version:      1.7
 * Plugin URI:   https://www.hansei.nl/plugins
 * Author:       Erik Jan de Wilde, (c) 2024, HanSei
 * Text Domain:  oko-pm
 * Domain Path:  /languages/
 * Network:      true
 * Requires PHP: 5.3
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

function oko_pm_shortcode()
{
    include_once "oko-pm.php";
    $wat = new OKO_pm();

    $ta = $wat->get_interface();
}

function oko_pm_register_shortcode()
{
    add_shortcode('show-oko-pm', 'oko_pm_shortcode');
}

add_action('init', 'oko_pm_register_shortcode');