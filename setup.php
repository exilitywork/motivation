<?php
/**
 * -------------------------------------------------------------------------
 * Motivation plugin for GLPI
 * Copyright (C) 2021 by the Belwest, Kapeshko Oleg.
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Motivation.
 *
 * Motivation is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Motivation is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Motivation. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

define('PLUGIN_MOTIVATION_MIN_GLPI_VERSION', '9.4');
define('PLUGIN_MOTIVATION_NAMESPACE', 'motivation');

/*
if (!defined("PLUGINFIELDSUPGRADE_DIR")) {
    define("PLUGINFIELDSUPGRADE_DIR", GLPI_ROOT . "/plugins/fieldsupgrade");
 }
 
 if (!defined("PLUGINFIELDSUPGRADE_DOC_DIR")) {
    define("PLUGINFIELDSUPGRADE_DOC_DIR", GLPI_PLUGIN_DOC_DIR . "/fieldsupgrade");
 }
 if (!file_exists(PLUGINFIELDSUPGRADE_DOC_DIR)) {
    mkdir(PLUGINFIELDSUPGRADE_DOC_DIR);
 }

if (!defined("PLUGINFIELDSUPGRADE_CLASS_PATH")) {
    define("PLUGINFIELDSUPGRADE_CLASS_PATH", PLUGINFIELDSUPGRADE_DOC_DIR . "/inc");
 }
 if (!file_exists(PLUGINFIELDSUPGRADE_CLASS_PATH)) {
    mkdir(PLUGINFIELDSUPGRADE_CLASS_PATH);
 }
 
 if (!defined("PLUGINFIELDSUPGRADE_FRONT_PATH")) {
    define("PLUGINFIELDSUPGRADE_FRONT_PATH", PLUGINFIELDSUPGRADE_DOC_DIR."/front");
 }
 if (!file_exists(PLUGINFIELDSUPGRADE_FRONT_PATH)) {
    mkdir(PLUGINFIELDSUPGRADE_FRONT_PATH);
 }
*/

/**
 * Plugin description
 *
 * @return boolean
 */
function plugin_version_motivation() {
    return [
      'name' => 'Calculation Motivation for Belwest',
      'version' => '0.1',
      'author' => 'BELWEST - Kapeshko Oleg',
      'homepage' => '',
      'license' => 'local',
      'minGlpiVersion' => PLUGIN_MOTIVATION_MIN_GLPI_VERSION,
    ];
}

/**
 * Initialize plugin
 *
 * @return boolean
 */
function plugin_init_motivation() {
    //if (Session::getLoginUserID()) {
        
        global $PLUGIN_HOOKS, $LANG ;
	
        $PLUGIN_HOOKS['csrf_compliant'][PLUGIN_MOTIVATION_NAMESPACE] = true;

        $PLUGIN_HOOKS['add_css'][PLUGIN_MOTIVATION_NAMESPACE][]='css/motivation.css';
        /*$PLUGIN_HOOKS['add_css'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/DataTables-1.10.24/css/jquery.dataTables.css';
        $PLUGIN_HOOKS['add_css'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/Buttons-1.7.0/css/buttons.dataTables.css';*/

        //$PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='../../lib/jquery/js/jquery-1.10.2.js';
        /*$PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/JSZip-2.5.0/jszip.js';
        $PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/pdfmake-0.1.36/pdfmake.js';
        $PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/pdfmake-0.1.36/vfs_fonts.js';
        $PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/DataTables-1.10.24/js/jquery.dataTables.js';
        $PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/Buttons-1.7.0/js/dataTables.buttons.js';
        $PLUGIN_HOOKS['add_javascript'][PLUGIN_MOTIVATION_NAMESPACE][]='lib/Buttons-1.7.0/js/buttons.html5.js';*/
        
        Plugin::registerClass('PluginMotivationProfile', ['addtabon' => 'Profile']);
        //Plugin::registerClass('PluginMotivationConfig', ['addtabon' => ['Entity']]);
        if(Session::haveRight('plugin_motivation_calc', READ)) {
            $PLUGIN_HOOKS["menu_toadd"][PLUGIN_MOTIVATION_NAMESPACE] = array('statistics'  => array('PluginMotivationConfig'));
        }

        $PLUGIN_HOOKS['config_page'][PLUGIN_MOTIVATION_NAMESPACE] = 'front/index.php';

        // Display fields in any existing tab
        //$PLUGIN_HOOKS['post_item_form'][PLUGIN_FIELDSUPGRADE_NAMESPACE] = ['PluginFieldsupgradeFieldsupgrade', 'post_item_form'];
        //$PLUGIN_HOOKS['post_show_tab'][PLUGIN_FIELDSUPGRADE_NAMESPACE] = ['PluginFieldsupgradeFieldsupgrade', 'post_show_tab'];
/* TODO
        //$PLUGIN_HOOKS['post_show_item'][PLUGIN_GROUPCATEGORY_NAMESPACE] = ['PluginGroupcategoryGroupcategory', 'post_show_item'];
        $PLUGIN_HOOKS['post_item_form'][PLUGIN_GROUPCATEGORY_NAMESPACE] = ['PluginGroupcategoryGroupcategory', 'post_item_form'];
        $PLUGIN_HOOKS['pre_item_update'][PLUGIN_GROUPCATEGORY_NAMESPACE] = [
          'Group' => 'plugin_groupcategory_group_update',
        ];
*/
        return true;
    //} else {
        //return false;
    //}
}

/**
 * Check if plugin prerequisites are met
 *
 * @return boolean
 */
function plugin_motivation_check_prerequisites() {
    $prerequisites_check_ok = false;

    try {
        if (version_compare(GLPI_VERSION, PLUGIN_MOTIVATION_MIN_GLPI_VERSION, '<')) {
            throw new Exception('This plugin requires GLPI >= ' . PLUGIN_MOTIVATION_MIN_GLPI_VERSION);
        }

        $prerequisites_check_ok = true;
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    return $prerequisites_check_ok;
}

/**
 * Check if config is compatible with plugin
 *
 * @return boolean
 */
function plugin_motivation_check_config() {
    // nothing to do
    return true;
}
