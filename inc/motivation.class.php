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

class PluginMotivationMotivation extends CommonDBTM {

    static $rightname = 'plugin_motivation_';

    /**
     * @param CommonGLPI $item
     * @param int        $withtemplate
     *
     * @return string|translated
     */
    function getTabNameForItem(CommonGLPI $item, $options = []) {
            return 'Контроль выполнения';
    }

    /**
     * @param CommonGLPI $item
     * @param int        $tabnum
     * @param int        $withtemplate
     *
     * @return bool
     */
    static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

        $input = [];
        $input['user_id'] = Session::getLoginUserID();
        $input['ticket_id'] = $item->fields['id'];
        $pref = new self();
        $pref->showForm($pref->getItemID($input['user_id'], $input['ticket_id']), $input);
        return true;
    }

    /**
     * @param number $item_id
     * @param array $input
     * @param array $options
     */
    function showForm($item_id, $input = [], $options = []) {
        global $DB;

        //If user open tab in first time, we set default values
        if (!$this->getFromDB($item_id)) {
            $item_id = $this->initFields($input);
            $this->getFromDB($item_id);
        }

        //Fields are not deletable & disable title
        $options['candel']  = false;
        $options['formtitle'] = false;

        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='tab_bg_2'><th colspan='3'>" . $this->getTypeName() . "</th></tr>";
        echo "</table>";
        
        
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'>";
        echo "<td>Ошибка разработчика</td><td>";
        if (Session::haveRight('plugin_executioncontrol_devmistake', UPDATE)) {
            Dropdown::showYesNo("devmistake", $this->fields['devmistake']);
        } else {
            echo $this->fields['devmistake'] ? "Да" : "Нет";
        }
        echo "</td>";
        echo "<td>Соблюдение сроков</td><td>";
        if (Session::haveRight('plugin_executioncontrol_deadline', UPDATE)) {
            Dropdown::showYesNo("deadline", $this->fields['deadline']);
        } else {
            echo $this->fields['deadline'] ? "Да" : "Нет";
        }
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td>Время выполнения</td><td>";
        if (Session::haveRight('plugin_executioncontrol_exectime', UPDATE)) {
            echo "<input id='exectime' type='number' name='exectime' style='width: 60px' min='1' value='".$this->fields['exectime']."'> мин.";
        } else {
            echo $this->fields['exectime']." мин.";
        }
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options);

        // select ticket's specialists with exectime
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_executioncontrol_fields',
            'WHERE' => ['ticket_id' => $input['ticket_id'],
                        'exectime' => ['>' , 0]]
        ]);

        $users = [];
        $used    = [];
        $count = count($iterator);
        $total_exectime = 0;
        
        // load firstname and surname of specialists
        if ($count) {
            while ($data = $iterator->next()) {
                $user = $DB->request([
                    'SELECT' => ['firstname', 'realname'],
                    'FROM' => 'glpi_users',
                    'WHERE' => ['id' => $data['user_id']]
                ])->next();
                $users[$data['id']] = $data;
                $users[$data['id']]['name'] = $user['realname'].' '.$user['firstname'];
            }
        }

        // print table of specialists
        echo "<div class='spaced'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th colspan='9'>Специалисты</th>";
        echo "</tr>";

        if ($count) {
            echo "<tr>";
            echo "<th>" . __('Имя') . "</th>";
            echo "<th>" . __('Последнее изменение') . "</th>";
            echo "<th>" . __('Время выполнения') . "</th>";
            echo "</tr>";

            foreach ($users as $data) {
                echo "<tr class='tab_bg_1 center'>";

                echo "<td>";
                echo $data['name'];
                echo "</td>";

                echo "<td>";
                echo $data['date_mod'];
                echo "</td>";

                echo "<td>";
                echo $data['exectime']." мин.";
                echo "</td>";
                $total_exectime += $data['exectime'];

                echo "</tr>";
            }
            echo "<tr class='tab_bg_1 center' style>
                    <th>Суммарное время выполенения: </td>
                    <th></th>
                    <th>".$total_exectime." мин. (".round($total_exectime/60, 1)." ч.)</th>
            </tr>";
        } else {
            echo "<tr class='tab_bg_1'>";
            echo "<td>";
            echo "Нет данных";
            echo "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    }

    /**
     * Returns the type name with consideration of plural
     *
     * @param number $nb Number of item(s)
     * 
     * @return string Itemtype name
     */
    public static function getTypeName($nb = 0) {
        return 'Контроль выполнения';
    }

    /**
     * @param array $input
     * 
     * @return number item ID
     */
    public function initFields($input = []) {
        global $DB;

        $DB->insertOrDie(
            'glpi_plugin_executioncontrol_fields', [
               'user_id'        => $input['user_id'],
               'ticket_id'      => $input['ticket_id'],
               'date_mod'       => date("Y-m-d H:i")
            ],
            'MySQL Error: Insert of item ExecutioncontrolField failed!'
        );

        return $DB->insert_id();
    }

    /**
     * Returns the type name with consideration of plural
     *
     * @param number $user_id
     * @param number $item_id ticket ID
     * 
     * @return number item ID
     */
    function getItemID($user_id, $item_id) {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => 'id',
            'FROM' => 'glpi_plugin_executioncontrol_fields',
            'WHERE' => ['user_id' => $user_id,
                        'ticket_id' => $item_id],
            'LIMIT' => 1]
        );
        if (count($iterator)) {
            while ($data = $iterator->next()) {
                return $data['id'];
            }
        }
    }
}