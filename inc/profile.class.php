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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}

class PluginMotivationProfile extends Profile {

    static $rightname = "profile";

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string|translated
     */
    function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
            return __('Графики и Мотивация');
    }

    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

        if ($item->getType() == 'Profile') {
            $ID   = $item->getID();
            $prof = new self();

            self::addDefaultProfileInfos($ID,
                                            [
                                                'plugin_motivation_graphs'        => 0,
                                                'plugin_motivation_calc'          => 0
                                            ]);

            $prof->showForm($ID);
        }

        return true;
    }

    /**
     * @param $ID
     */
    static function createFirstAccess($ID) {
        
        self::addDefaultProfileInfos($ID,
                                        [
                                            'plugin_motivation_graphs'        => 3,
                                            'plugin_motivation_calc'          => 3
                                        ]);

    }

    /**
     * @param      $profiles_id
     * @param      $rights
     * @param bool $drop_existing
     *
     * @internal param $profile
     */
    static function addDefaultProfileInfos($profiles_id, $rights, $drop_existing = false)
    {
        $dbu = new DbUtils();
        $profileRight = new ProfileRight();
        foreach ($rights as $right => $value) {
            if ($dbu->countElementsInTable('glpi_profilerights',
                    ["profiles_id" => $profiles_id, "name" => $right]) && $drop_existing) {
                $profileRight->deleteByCriteria(['profiles_id' => $profiles_id, 'name' => $right]);
            }
            if (!$dbu->countElementsInTable('glpi_profilerights',
                ["profiles_id" => $profiles_id, "name" => $right])) {
                $myright['profiles_id'] = $profiles_id;
                $myright['name'] = $right;
                $myright['rights'] = $value;
                $profileRight->add($myright);

                //Add right to the current session
                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    /**
     * Show profile form
     *
     * @param int $profiles_id
     * @param bool $openform
     * @param bool $closeform
     *
     * @return nothing
     * @internal param int $items_id id of the profile
     * @internal param value $target url of target
     */
    function showForm($profiles_id = 0, $openform = true, $closeform = true)
    {

        echo "<div class='firstbloc'>";
        if (($canedit = Session::haveRightsOr(self::$rightname, [CREATE, UPDATE]))
            && $openform) {
            $profile = new Profile();
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        $profile = new Profile();
        $profile->getFromDB($profiles_id);
            $rights = $this->getAllRights();
            $profile->displayRightsChoiceMatrix($rights, ['canedit' => $canedit,
                'default_class' => 'tab_bg_2',
                'title' => 'Графики и Мотивация']
            );

        if ($canedit && $closeform) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $profiles_id]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }
        echo "</div>";
    }

    static function uninstallProfile()
    {
        $pfProfile = new self();
        $a_rights = $pfProfile->getRightsGeneral();
        foreach ($a_rights as $data) {
            ProfileRight::deleteProfileRights([$data['field']]);
        }
    }

    /**
     * @param bool $all
     *
     * @return array
     */
    static function getAllRights($all = false)
    {
        $rights = [
            ['rights' => [READ => __('Read'), UPDATE => __('Update')],
                'label' => 'График дежурств',
                'field' => 'plugin_motivation_graphs'],
            ['rights' => [READ => __('Read'), UPDATE => __('Update')],
                'label' => 'Расчет мотивации',
                'field' => 'plugin_motivation_calc'],
        ];

        return $rights;
    }

    /**
     * Returns the type name with consideration of plural
     *
     * @param number $nb Number of item(s)
     * @return string Itemtype name
     */
    /*public static function getTypeName($nb = 0) {
        //return __('Alerts', 'news');
        return 'Общие';
    }*/

    /**
     * @param $users_id
     */
    /*public function initPreferences($users_id) {

        $input                 = [];
        $input['id']           = $users_id;
        $input['preload'] = "1";
        $this->add($input);

    }*/
}