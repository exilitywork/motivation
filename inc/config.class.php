<?php
/*
 *
 -------------------------------------------------------------------------
 Plugin GLPI News
 Copyright (C) 2015 by teclib.
 http://www.teclib.com
 -------------------------------------------------------------------------
 LICENSE
 This file is part of Plugin GLPI News.
 Plugin GLPI News is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.
 Plugin GLPI News is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.
 You should have received a copy of the GNU General Public License
 along with Plugin GLPI News. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
*/

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMotivationConfig extends CommonDBTM {

    /**
     * Returns the type name with consideration of plural
     *
     * @param number $nb Number of item(s)
     * @return string Itemtype name
     */
    public static function getTypeName($nb = 0) {
        return 'Мотивация';
    }

    /**
    *  @see CommonGLPI::getMenuContent()
    *
    *  @since version 0.5.6
   **/
   static function getMenuContent() {
    global $CFG_GLPI;

    $menu = array();

    $menu['title']   = "Мотивация";
    $menu['page']    = '/plugins/motivation/front/motivation.php';
    $menu['default'] = '/plugins/motivation/front/motivation.php';

    return $menu;
}
}