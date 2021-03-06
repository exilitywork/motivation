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

include ("/var/www/glpi/plugins/motivation/lib/SimpleXLSX.php");

class PluginMotivationMotivation extends CommonDBTM {

    static $rightname = 'plugin_motivation_calc';

    protected $year;
    protected $month;
    protected $last_day;
    protected $codes;
    protected $graphs;
    protected $statistics;

    /**
     * Constructor
     */
    //function __construct($year, $month, $params = []) {
        function initCalc($year, $month, $params = []) {

        $this->year = $year;
        $this->month = $month;
        if ($month == date('n') && $year == date('Y')) {
            $this->last_day = date('j');
        } else {
            $this->last_day = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        }
        $this->codes = $this->getCodes($this->year, $this->month);
        $this->graphs = $this->getGraphs($this->year, $this->month);
        $this->statistics = $this->getStatisctics();

    }

    /**
     * ?????????????????? ???????????????????????? ?????????? ???? ?????????????? ???????????????? ??????
     *
     * @param number $time
     * 
     * @return number $norm
     */
    function getNorm($time) {

        switch ($time) {
            case '10': $norm = 1; break;
            case '9': $norm = 0.9; break;
            case '8': $norm = 0.8; break;
            case '7': $norm = 0.7; break;
            case '5': $norm = 0.5; break;
            case '4': $norm = 0.4; break;
            default: $norm = 1;
        }
        
        return $norm;
    }

    /**
     * ???????????????????????? ?????????????? ???????????? ???? ?????????? ???? ????
     *
     * @param number $year
     * @param number $month
     * 
     * @return number item ID
     */
    function getCodes($year, $month) {
        global $DB;
        $codes = [];

        $iterator = $DB->request([
            'SELECT'    => ['code', 'time_begin', 'time_end', 'dinner_begin', 'dinner_end'],
            'FROM'      => 'glpi_plugin_motivation_codes',
            'WHERE'     => [
                'year'  => $year,
                'month' => $month
            ]
        ]);
        if (count($iterator)) {
            while ($data = $iterator->next()) {
                $dinner = ((strtotime(str_replace('-',':',$data['dinner_end'])) - strtotime(str_replace('-',':',$data['dinner_begin']))) / 60);
                $codes[$data['code']]['time'] = ((strtotime(str_replace('-',':',$data['time_end'])) - strtotime(str_replace('-',':',$data['time_begin']))) / 60 - $dinner);
                switch ($codes[$data['code']]['time']) {
                    case '600': $codes[$data['code']]['norm'] = 1; break;
                    case '540': $codes[$data['code']]['norm'] = 0.9; break;
                    case '480': $codes[$data['code']]['norm'] = 0.8; break;
                    case '420': $codes[$data['code']]['norm'] = 0.7; break;
                    case '300': $codes[$data['code']]['norm'] = 0.5; break;
                    case '240': $codes[$data['code']]['norm'] = 0.4; break;
                    default: $codes[$data['code']]['norm'] = 1;
                }
                foreach ($data as $key => $value) {
                    if ($key != 'code') {
                        $codes[$data['code']][$key] = $value;
                    }
                }
            }
        }
        return $codes;
    }

    /**
     * ???????????????????????? ?????????????? ???????????? ???? ?????????????? ???? ????
     *
     * @param number $year
     * @param number $month
     * 
     * @return number item ID
     */
    function getGraphs($year, $month) {
        global $DB;
        $graphs = [];

        $iterator = $DB->request([
            'SELECT'    => ['user_id', 'line', 'graph', 'plan', 'penalty', 'line2koef', 'oldsmart', 'notification', 'retail'],
            'FROM'      => 'glpi_plugin_motivation_graphs',
            'WHERE'     => [
                'year'  => $year,
                'month' => $month
            ]
        ]);
        if (count($iterator)) {
            while ($data = $iterator->next()) {
                if (!isset($this->statistics['retail'])) {
                    $this->statistics['retail'] = $data['retail'];
                }
                $graphs[$data['user_id']]['line']   = $data['line'];
                $graphs[$data['user_id']]['graph']  = $data['graph'];
                $this->statistics['users'][$data['user_id']]['plan']         = $data['plan'];
                $this->statistics['users'][$data['user_id']]['penalty']      = $data['penalty'];
                $this->statistics['users'][$data['user_id']]['line2koef']    = $data['line2koef'];
                $this->statistics['users'][$data['user_id']]['notification'] = isset($data['notification']) ? $data['notification'] : '';
                if (isset($data['oldsmart'])) {
                    $this->statistics['users'][$data['user_id']]['oldsmart'] = $data['oldsmart'];
                }
                for ($day = 1; $day <= strlen($data['graph']); $day++) {
                    if ($data['graph'][$day-1] > 0) {
                        $this->statistics['users'][$data['user_id']][$day]['time'] = $this->codes[$data['graph'][$day-1]]['time'];
                        $this->statistics['users'][$data['user_id']][$day]['plan'] = $this->getNorm($this->codes[$data['graph'][$day-1]]['time'] / 60);
                    }
                }
                $this->statistics['users'][$data['user_id']]['line'] = $data['line'];
                $this->statistics['users'][$data['user_id']]['name'] = getUserName($data['user_id']);
            }
        }
        return $graphs;
    }

    /**
     * ???????????????????????? ?????????????? ???????????? ???? ??????????????????
     *
     * @param number $year
     * @param number $month
     * 
     * @return number item ID
     */
    function getStatisctics() {
        global $DB;

        $statistics = $this->statistics;
        $group_id = 33;
        $year = $this->year;
        $month = str_pad($this->month, 2, '0', STR_PAD_LEFT);

        // ?????????????????? ???????????????????? ???? ?????????????? ?????????????????????????? ?? ?????????????? ???? ????????????????????
        $statistics['total']['total'] = 0;
        $statistics['in_work']['total'] = 0;
        for ($day = 1; $day <= $this->last_day; $day++) {
            $sql_date = "'".$year."-".$month."-".str_pad($day, 2, '0', STR_PAD_LEFT)." 00:00:00' AND '".$year."-".$month."-".str_pad($day, 2, '0', STR_PAD_LEFT)." 23:59:59'";

            $total = $DB->result($DB->query("
                SELECT count(*) AS total
                FROM glpi_groups_tickets, glpi_tickets, glpi_groups
                WHERE glpi_groups_tickets.groups_id = ".$group_id."
                    AND glpi_groups_tickets.groups_id = glpi_groups.id
                    AND glpi_groups_tickets.tickets_id = glpi_tickets.id
                    AND glpi_tickets.is_deleted = 0
                    AND glpi_tickets.date BETWEEN ".$sql_date), 0, 'total');
            
            $total_close = $DB->result($DB->query("
                SELECT count(*) AS total
                FROM glpi_groups_tickets, glpi_tickets, glpi_groups
                WHERE glpi_groups_tickets.groups_id = ".$group_id."
                    AND glpi_groups_tickets.groups_id = glpi_groups.id
                    AND glpi_groups_tickets.tickets_id = glpi_tickets.id
                    AND glpi_tickets.is_deleted = 0
                    AND glpi_tickets.closedate BETWEEN ".$sql_date), 0, 'total');

            $stat_query = $DB->query("
                SELECT glpi_groups_users.users_id AS id, count(glpi_tickets_users.id) AS count
                FROM glpi_groups_users, glpi_tickets_users, glpi_users, glpi_groups_tickets, glpi_tickets
                WHERE glpi_groups_tickets.groups_id = ".$group_id."
                    AND glpi_tickets_users.users_id = glpi_groups_users.users_id
                    AND glpi_tickets_users.users_id = glpi_users.id
                    AND glpi_tickets.id = glpi_tickets_users.tickets_id
                    AND glpi_tickets.id = glpi_groups_tickets.tickets_id
                    AND glpi_groups_users.groups_id = glpi_groups_tickets.groups_id
                    AND glpi_tickets.closedate BETWEEN ".$sql_date."
                    AND glpi_tickets.is_deleted = 0
                    AND glpi_tickets_users.type = 2
                    AND glpi_tickets.entities_id IN (0,0)
                GROUP BY id
                ORDER BY count DESC
                LIMIT 10
            ");

            $smart_query = $DB->query("
                SELECT glpi_groups_users.users_id AS id,
                    SUM(case when glpi_tickets.requesttypes_id = 9 then 1 else 0 end) AS smart_task,
                    SUM(case when glpi_tickets.requesttypes_id = 10 then 1 else 0 end) AS smart_promo
                FROM glpi_groups_users, glpi_tickets_users, glpi_users, glpi_groups_tickets, glpi_tickets
                WHERE glpi_groups_tickets.groups_id = ".$group_id."
                    AND glpi_tickets_users.users_id = glpi_groups_users.users_id
                    AND glpi_tickets_users.users_id = glpi_users.id
                    AND glpi_tickets.id = glpi_tickets_users.tickets_id
                    AND glpi_tickets.id = glpi_groups_tickets.tickets_id
                    AND glpi_groups_users.groups_id = glpi_groups_tickets.groups_id
                    AND glpi_tickets.closedate BETWEEN ".$sql_date."
                    AND glpi_tickets.is_deleted = 0
                    AND glpi_tickets_users.type = 2
                    AND glpi_tickets.entities_id IN (0,0)
                GROUP BY id
                LIMIT 10
            ");

            $statistics['total'][$day] = $total;
            $statistics['in_work'][$day] = $total_close;
            while ($stat_row = $DB->fetch_assoc($stat_query)) {
                $statistics['users'][$stat_row['id']][$day]['count'] = $stat_row['count'];
                $statistics['users'][$stat_row['id']]['total']['count'] = isset($statistics['users'][$stat_row['id']]['total']['count']) 
                                                                        ? $statistics['users'][$stat_row['id']]['total']['count'] + $stat_row['count']
                                                                        : $stat_row['count'];
            }
            while ($smart_row = $DB->fetch_assoc($smart_query)) {
                $statistics['users'][$smart_row['id']]['total']['smart_task'] = isset($statistics['users'][$smart_row['id']]['total']['smart_task'])
                                                                        ? $statistics['users'][$smart_row['id']]['total']['smart_task'] + $smart_row['smart_task']
                                                                        : $smart_row['smart_task'];
                $statistics['users'][$smart_row['id']]['total']['smart_promo'] = isset($statistics['users'][$smart_row['id']]['total']['smart_promo'])
                                                                        ? $statistics['users'][$smart_row['id']]['total']['smart_promo'] + $smart_row['smart_promo']
                                                                        : $smart_row['smart_promo'];
                $statistics['users'][$smart_row['id']][$day]['smart'] = $smart_row['smart_task'];
                $statistics['users'][$smart_row['id']]['total']['smart'] = isset($statistics['users'][$smart_row['id']]['total']['smart']) 
                                                                        ? $statistics['users'][$smart_row['id']]['total']['smart'] + $statistics['users'][$smart_row['id']][$day]['smart']
                                                                        : $statistics['users'][$smart_row['id']][$day]['smart'];
            }
            $statistics['percent'][$day] = round($statistics['in_work'][$day] / $statistics['total'][$day] * 100, 2);
            $statistics['total']['total'] += $total;
            $statistics['in_work']['total'] += $statistics['in_work'][$day];
            $statistics['percent']['total'] = round($statistics['in_work']['total'] / $statistics['total']['total'] * 100, 2);
        }

        // ???????????? ?????????????????????? ?????????? ?? ???????????????????? ??????????????????????
        for ($day = 1; $day <= $this->last_day; $day++) {
            $line1 = $line2 = 0;
            foreach ($statistics['users'] as $id => $user) {
                if (isset($statistics['total'][$day]) && isset($user[$day]['time']) && isset($user['line'])) {
                    $line1 = ($user['line'] == '1') ? ($line1 + 1) : $line1;
                    $line2 = ($user['line'] == '2') ? ($line2 + 1) : $line2;
                }
            }
            if ($line1 && $line2) {
                $statistics['line2'][$day] = round(100 / ($line1 + 2 * $line2), 2);
                $statistics['line1'][$day] = round((100 - $line2 * $statistics['line2'][$day]) / $line1, 2);
            } else {
                $statistics['line1'][$day] = $statistics['line2'][$day] = round(100 / ($line1 + $line2), 2);
            }
        }

        // ???????????? ?????????? ?? ???????????????? ?????? ????????????????????
        $user['total']['plan'] = 0;
        for ($day = 1; $day <= $this->last_day; $day++) {
            foreach ($statistics['users'] as $id => $user) {
                if (isset($user[$day]['time']) && $user['line'] == 0) {
                    $user[$day]['plan'] = '';
                }
                if (isset($user[$day]['time']) && $user['line'] > 0 && isset($user[$day]['count'])) {
                    if ($statistics['line'.$user['line']][$day] > 0) {
                        $user[$day]['plan'] = round($statistics['in_work'][$day] * $statistics['line'.$user['line']][$day] / 100 * $user[$day]['plan']);
                    } else {
                        $user[$day]['plan'] = $user[$day]['count'];
                    }
                    $user[$day]['percent'] = round($user[$day]['count'] / $user[$day]['plan'] * 100, 1);
                    if($user[$day]['percent'] < 100 && isset($user[$day]['smart']) && $user[$day]['smart'] > 0) {
                        $user[$day]['percent'] = 100;
                        $user[$day]['plan'] = $user[$day]['count'];
                    }
                }
                if (isset($user[$day]['plan']) && $user[$day]['plan'] != '') {
                    $plan = $user[$day]['plan'];
                } else {
                    $plan = 0;
                }
                $user['total']['plan'] = isset($user['total']['plan']) ? $user['total']['plan'] + $plan : $plan;
                if ($day == $this->last_day && isset($user['line']) && $user['line'] > 0) {
                    $user['total']['percent'] = round($user['total']['count'] / $user['total']['plan'] * 100);
                } else {
                    $user['total']['percent'] = 100;
                }
                $statistics['users'][$id] = $user;
            }
        }

        return $statistics;
        }

    /**
     * ???????????????? ???????????? ???????????????????? ??????????????, ?????????? ?? ?????????????? ?????? ????????
     * @param array $input
     * @param array $files
     * 
     * @return number item ID
     */
    function loadData($input = [], $files = []) {
        global $DB;

        $this->year = $input['year'];
        $this->month = $input['month'];
        if (isset($files['code_file']['tmp_name']) && $files['code_file']['tmp_name'] != '') {
            $input['files'] = true;
        }

        // ?????????????????? ???????????????? ???????????????????? (??????????, ??????????, ??????)
        foreach ($input as $param => $value) {
            if (strpos($param, "id") !== false) {
                $lines[substr($param, 2)] = $value;
            }
        }

        // ???????????????? ???????????? ?????????????? ?? ??????????
        $file_graph = PLUGINMOTIVATION_DOC_DIR.'/sap.xlsx';
        $file_codes = PLUGINMOTIVATION_DOC_DIR.'/code.xlsx';

        if (isset($files['code_file']['tmp_name']) && $files['code_file']['tmp_name'] != '') {
            $ext = explode('.', $files['code_file']['name']);
            if(in_array(array_pop($ext), ['xlsx']))
            {
                if (move_uploaded_file($files['code_file']['tmp_name'], $file_codes)) {
                    // ???????????? ?? ???? ???????????? ?????????? ??????????
                    if ($xlsx = SimpleXLSX::parse($file_codes)) {
                        $header_values = $rows = [];
                        foreach ($xlsx->rows() as $key => $raw) {
                            if ($key > 0) {
                                $this->updateCodes(array_merge($input, $raw));
                            }
                        }
                    } else {
                        die('?????? ?????????? ??????????!');
                    }
                }
            } else {
                die("???????????????? ???????????? ??????????!\n");
            }
        }
        if (isset($files['graph_file']['tmp_name']) && $files['graph_file']['tmp_name'] != '') {
            $ext = explode('.', $files['graph_file']['name']);
            if(in_array(array_pop($ext), array('xlsx')))
            {
                if (move_uploaded_file($files['graph_file']['tmp_name'], $file_graph)) {
                    // ???????????? ?? ???? ???????????? ?????????? ??????????????
                    if ( $xlsx = SimpleXLSX::parse($file_graph)) {
                        $header_values = $rows = [];
                        foreach ($xlsx->rows() as $key => $raw) {
                            if ($key > 0) {
                                $tab_num = str_pad($raw[0], 8, "0", STR_PAD_LEFT);
                                unset($raw['0']);
                                $iterator = $DB->request([
                                    'SELECT' => ['id','realname', 'firstname'],
                                    'FROM' => 'glpi_users',
                                    'WHERE' => ['registration_number' => $tab_num],
                                    'LIMIT' => 1]
                                );
                                if (count($iterator)) {
                                    while ($data = $iterator->next()) {
                                        $input['user_id'] = $data['id'];
                                        $input['line'] = isset($lines[$data['id']]) ? $lines[$data['id']] : 1;
                                        $input['graph'] = '';
                                        foreach ($raw as $day => $code) {
                                            $input['graph'] = $input['graph'].($code == '' ? '0' : $code);
                                        }
                                        $this->updateGraph($input);
                                    }
                                }
                            }
                        }
                        return;
                    } else {
                        die('?????? ?????????? ??????????????!');
                    }
                }
            } else {
                die("???????????????? ???????????? ??????????!\n");
            }
        }
        if (isset($lines)) {
            foreach ($lines as $id => $line) {
                $input['user_id'] = $id;
                $input['line'] = $line;
                $this->graphs[$id]['line'] = $line;
                $this->statistics['users'][$id]['line'] = $line;
                $this->updateGraph($input);
            }
        }

        // ???????????? ?? ???? ???????????? ?????????? ??????????
        /*if ($xlsx = SimpleXLSX::parse($file_codes)) {
            // Produce array keys from the array values of 1st array element
            $header_values = $rows = [];
            foreach ($xlsx->rows() as $key => $raw) {
                if ($key > 1) {
                    $this->updateCodes(array_merge($input, $raw));
                }
            }
        } else {
            die('?????? ?????????? ??????????!');
        }*/
        
        // ???????????? ?? ???? ???????????? ?????????? ??????????????
        /*f ( $xlsx = SimpleXLSX::parse($file_graph)) {
            $header_values = $rows = [];
            foreach ($xlsx->rows() as $key => $raw) {
                if ($key > 0) {
                    $tab_num = str_pad($raw[0], 8, "0", STR_PAD_LEFT);
                    unset($raw['0']);
                    $iterator = $DB->request([
                        'SELECT' => ['id','realname', 'firstname'],
                        'FROM' => 'glpi_users',
                        'WHERE' => ['registration_number' => $tab_num],
                        'LIMIT' => 1]
                    );
                    if (count($iterator)) {
                        while ($data = $iterator->next()) {
                            $input['user_id'] = $data['id'];
                            $input['line'] = isset($lines[$data['id']]) ? $lines[$data['id']] : 1;
                            $input['graph'] = '';
                            foreach ($raw as $day => $code) {
                                $input['graph'] = $input['graph'].($code == '' ? '0' : $code);
                            }
                            $this->updateGraph($input);
                        }
                    }
                }
            }
        } else {
            die('?????? ?????????? ??????????????!');
        }*/
        return;
    }

    /**
     * ???????????????? / ???????????????????? ?????????????? ?? ?????????????? ??????????
     * @param array $input
     * 
     * @return number item ID
     */
    public function updateCodes($input = []) {
        global $DB;
        
        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_motivation_codes', 
            'WHERE' => [
                'code'  => $input[0],
                'year'  => $input['year'],
                'month' => $input['month']
            ]
        ]);
        if (count($iterator)) {
            $DB->updateOrDie(
                'glpi_plugin_motivation_codes', [
                    'time_begin'            => $input[1],
                    'time_end'              => $input[2],
                    'dinner_begin'          => $input[3],
                    'dinner_end'            => $input[4]
                ], [
                    'year'                  => $input['year'],
                    'month'                 => $input['month'],
                    'code'                  => $input[0]
                ],
                'MySQL Error: Update of item '.$input[0].' motivation code failed!'
            ); 
        } else {
            $DB->insertOrDie(
                'glpi_plugin_motivation_codes', [
                    'year'                  => $input['year'],
                    'month'                 => $input['month'],
                    'code'                  => $input[0],
                    'time_begin'            => $input[1],
                    'time_end'              => $input[2],
                    'dinner_begin'          => $input[3],
                    'dinner_end'            => $input[4]
                ],
                'MySQL Error: Insert of item '.$input[0].' motivation code failed!'
            );
        }

        return true;
    }

    /**
     * ???????????????? / ???????????????????? ?????????????? ?? ?????????????? ????????????????
     * @param array $input
     * 
     * @return number item ID
     */
    public function updateGraph($input = []) {
        global $DB;

        if (($input['month'] - 1) == 0) {
            $old_month = 12;
            $old_year = $input['year'] - 1;
        } else {
            $old_month = $input['month'] - 1;
            $old_year = $input['year'];
        }

        $old_smart = $DB->result($DB->query("
            SELECT newsmart
            FROM glpi_plugin_motivation_graphs
            WHERE user_id = ". $input['user_id']."
                AND year = ".$old_year."
                AND month = ".$old_month), 0, 'newsmart'
        );

        $iterator = $DB->request([
            'FROM' => 'glpi_plugin_motivation_graphs', 
            'WHERE' => [
                'user_id'  => $input['user_id'],
                'year'  => $input['year'],
                'month' => $input['month']
            ],
            'LIMIT' => 1
        ]);
        if (count($iterator)) {
            foreach ($iterator as $data) {
                $graph = $data['graph'];
            }
            $DB->updateOrDie(
                'glpi_plugin_motivation_graphs', [
                    'graph'                 => isset($input['graph']) ? $input['graph'] : $graph,
                    'line'                  => $input['line'],
                    'oldsmart'              => isset($old_smart) ? $old_smart : null
                ], [
                    'year'                  => $input['year'],
                    'month'                 => $input['month'],
                    'user_id'               => $input['user_id']
                ],
                'MySQL Error: Update of item '.$input['user_id'].' motivation graph failed!'
            ); 
        } else {
            if (isset($input['files'])) {
                $DB->insertOrDie(
                    'glpi_plugin_motivation_graphs', [
                        'user_id'               => $input['user_id'],
                        'year'                  => $input['year'],
                        'month'                 => $input['month'],
                        'graph'                 => $input['graph'],
                        'line'                  => $input['line'],
                        'plan'                  => isset($input['plan']) ? $input['plan'] : null,
                        'penalty'               => isset($input['penalty']) ? $input['penalty'] : null,
                        'line2koef'             => isset($input['line2koef']) ? $input['line2koef'] : null,
                        'oldsmart'              => isset($old_smart) ? $old_smart : null,
                        'newsmart'              => isset($input['newsmart']) ? $input['newsmart'] : null,
                        'notification'          => isset($input['notification']) ? $input['notification'] : null,
                        'retail'                => isset($input['retail']) ? $input['retail'] : null,
                    ],
                    'MySQL Error: Insert of item '.$input['user_id'].' motivation graph failed!'
                );
            }
        }

        return true;
    }

    /**
     * ???????????? ???????????? ???? ??????????????
     * @param array $input
     * 
     * @return number item ID
     */
    static function saveData($input, $year, $month) {
        global $DB;

        if(is_array($input)) {
            foreach ($input as $record) {
                $resp = $DB->update(
                    'glpi_plugin_motivation_graphs', [
                        'plan'                  => $record[1] ? true : false,
                        'penalty'               => $record[2] ? true : false,
                        'line2koef'             => $record[3] != '' ? $record[3] : null,
                        'oldsmart'              => $record[4] >= 0 ? $record[4] : null,
                        'newsmart'              => $record[5] >= 0 ? $record[5] : null,
                        'notification'          => $record[6] != '' ? $record[6] : null,
                        'retail'                => $record[7] >= 0 ? $record[7] : null,
                    ], [
                        'year'                  => $year,
                        'month'                 => $month,
                        'user_id'               => $record[0]
                    ]
                );
                if (!$resp) {
                    $result[$record[0]] = $resp;
                }
            }
        } else {
            return false;
        }
        return isset($result) ? $result : true;
    }

    /**
     * ?????????? ?????????????? ??????????
     * @param array $input
     * 
     * @return number item ID
     */
    public function showCodes($input = []) {
        
        echo '<div class="b center c_ssmenu2" style="margin: 10px">?????????????? ??????????</div>';
        echo '<table id="codes" class="graph codes">';
        echo '
            <thead>
                <tr>
                    <th>??????</th>
                    <th colspan="2">?????????? ????????????</th>
                    <th colspan="2">?????????? ??????????</th>
                    <th>?????????????????????? ??????????</th>
                </tr>
            </thead>
            <tbody>';
        foreach ($this->codes as $id => $code) {
            echo '
                <tr>
                    <td>'.$id.'</td>
                    <td>'.$code['time_begin'].'</td>
                    <td>'.$code['time_end'].'</td>
                    <td>'.$code['dinner_begin'].'</td>
                    <td>'.$code['dinner_end'].'</td>
                    <td>'.$code['time'].' ??????.</td>
                </tr>';
        }
        echo '</tbody>';
        echo '</table>';

    }

    /**
     * ?????????? ?????????????? ????????????
     * @param array $input
     * 
     * @return number item ID
     */
    public function showGraphs($input = []) {

        $statistics = $this->statistics;

        echo "<form name='form' method='post' action='motivation.php' enctype='multipart/form-data' >";
        echo '<div class="b center c_ssmenu2">????????????</div>';
        echo '<table id="graph" class="center graph">';
        echo '
            <thead>
                <tr>
                    <th>??????????</th>
                    <th>??????????????????</th>';
        for ($i = 1; $i <= cal_days_in_month(CAL_GREGORIAN, $this->month, $this->year); $i++) {
            echo '<th>'.$i.'</th>';
        }
        echo '
                </tr>
            </thead>
            <tbody>';
        foreach ($this->graphs as $id => $graph) {
            if (Session::haveRight('plugin_motivation_graphs', UPDATE)) {
                $line = Dropdown::showFromArray('id'.$id, [0, 1, 2], ['value' => $graph['line'], 'display_emptychoice' => true, 'display' => false]);
            } else {
                $line = $graph['line'] == 0 ? '-----' : $graph['line'];
            }
            echo '
            <tr class="tab_bg_2 center">
                <td>'.$line.'</td>
                <th style="text-align: left">'.getUserName($id).'</th>
                <td>'.implode('</td><td>',str_split(str_replace('0', ' ', $graph['graph']))).'</td>
            </tr>';
        }
        echo '</tbody>
            </table>';
        
        echo '<input type="hidden" name="year" value="'.$this->year.'">';
        echo '<input type="hidden" name="month" value="'.$this->month.'">';
        echo "<div class='center'>";
        if (Session::haveRight('plugin_motivation_graphs', UPDATE)) {
            echo "<input type='submit' name='import' value=\""._sx('button', '????????????????????')."\" class='submit'>";
        }
        echo "</div>";
        Html::closeForm();
    }

    /**
     * ???????????????????????? ?? ?????????? ???????????????? ?????????????????? ?????????????? ??????????????????
     * @param array $input
     * 
     * @return number item ID
     */
    public function showCalc($input = []) {

        $statistics = $this->statistics;

        $retail = isset($this->statistics['retail']) ? $this->statistics['retail'] : 0;
        switch (true) {
            case $retail < 80:
                $retail_coef = 0.09;
                break;
            case $retail >= 80 && $retail < 90:
                $retail_coef = 0.12;
                break;
            case $retail >= 90 && $retail < 100:
                $retail_coef = 0.15;
                break;
            default:
                $retail_coef = 0.165;
        }

        echo '
            <div class="b center c_ssmenu2">???????????? ?????????????????? ????????????????????????</div>
            <div class="center">???????????????????? ?????????? ???? ???????????????????? ??????????????????????????: <input id="retail" type="number" min="0" step="1" style="width: 3em;" value="'.$retail.'">%</div>
            <table id="calc" class="center graph">
                <thead>
                    <tr>
                        <th>??????????????????</th>
                        <th>????????</th>
                        <th>??????????????????</th>
                        <th>SMART</th>
                        <th>??????????????</th>
                        <th>????????????????</th>
                        <th>????????. 2 ??????????</th>
                        <th>????????. ??????????</th>
                        <th></th>
                        <th>SMART ??????-????</th>
                        <th>?? ????????. ????????????</th>
                        <th>??????????????</th>
                        <th>???? ????????. ??????????</th>
                        <th>??????????-??????????</th>
                        <th>????????????????????</th>
                    </tr>
                </thead>
                <tbody>';
        foreach ($statistics['users'] as $user_id => $user) {
            if(isset($user['name'])) {
                $smart = isset($user['total']['smart_task']) ? $user['total']['smart_task'] : 0;
                $smart_promo = isset($user['total']['smart_promo']) ? $user['total']['smart_promo'] : 0;
                if (isset($user['plan'])) {
                    $plan_??oef = $user['plan'] ? 0.4 : 0;
                } else {
                    $plan_??oef = $user['total']['percent'] >= 100 ? 0.4 : 0;
                }
                $penalty = isset($user['penalty']) ? $user['penalty'] : true;
                $old_smart = isset($user['oldsmart']) ? $user['oldsmart'] : 0;
                $total_smart = $smart + $old_smart;
                $smart_??oef = $total_smart >= 10 ? 0.2 : 0;
                $new_smart = $total_smart >= 10 ? $total_smart - 10 : $total_smart;
                $total_??oef = $plan_??oef + ($penalty ? 0.25 : 0) + $smart_??oef + $retail_coef;
                switch (true) {
                    case $total_??oef <= 0.54:
                        $percent = 10;
                        break;
                    case $total_??oef >= 0.55 && $total_??oef <= 0.69:
                        $percent = 15;
                        break;
                    case $total_??oef >= 0.70 && $total_??oef <= 0.79:
                        $percent = 20;
                        break;
                    case $total_??oef >= 0.80 && $total_??oef <= 0.89:
                        $percent = 25;
                        break;
                    case $total_??oef >= 0.90 && $total_??oef <= 0.99:
                        $percent = 30;
                        break;
                    default:
                        $percent = 45;
                }
                $total_percent = $percent;
                if (isset($user['line2koef']) && $user['line2koef'] > 0) {
                    $total_percent = $percent * $user['line2koef'];
                }
                echo '<tr class="center">
                        <th id="'.$user_id.'" style="text-align: left">'.$user['name'].'</th>
                        <td>'.Dropdown::showFromArray('plan_'.$user_id, ['0', '0.4'], ['value' => ($plan_??oef ? 1 : 0), 'display' => false]).'</td>
                        <td>'.Dropdown::showFromArray('penalty_'.$user_id, ['0', '0.25'], ['value' => ($penalty ? 1 : 0), 'display' => false]).'</td>
                        <td>'.Dropdown::showFromArray('smart_'.$user_id, ['0', '0.2'], ['value' => ($smart_??oef ? 1 : 0), 'display' => false]).'</td>
                        <td>'.$retail_coef.'</td>
                        <td>'.$total_??oef.'</td>
                        <td><input type="number" min="1" step="0.1" class="line2koef" value="'.$user['line2koef'].'"style="width: 5em;"></td>
                        <td><input type="hidden" value="'.$percent.'">'.$total_percent.'</td>
                        <td></td>
                        <td>'.$total_smart.'</td>
                        <td><input type="number" min="0" step="1" class="oldsmart" value="'.$old_smart.'" style="width: 5em;"></td>
                        <td>'.$smart.'</td>
                        <td>'.$new_smart.'</td>
                        <td>'.$smart_promo.'</td>
                        <td><input type="text" value="'.(isset($user['notification']) ? $user['notification'] : '').'"></td>
                </tr>';
            }
        }
        echo '</tbody>
            </table>';
            if (Session::haveRight('plugin_motivation_calc', UPDATE)) {
            echo "<div class='center'><a class='vsubmit' onclick='saveCalc()' title=\""._sx('button', '??????????????????')."\">"._sx('button', '??????????????????')."</a></div>";

            echo '<script>
                // ?????????????????? ???????????????? ???????????????????? ?????????? ??????????????
                $("#retail").on("change", function() {
                    //console.log($(this).val());
                    let retail = $(this).val();

                    //let td = $(this).closest("tr").children("td");
                    switch (true) {
                        case retail < 80:
                            retail = 0.09;
                            break;
                        case retail >= 80 && retail < 90:
                            retail = 0.12;
                            break;
                        case retail >= 90 && retail < 100:
                            retail = 0.15;
                            break;
                        default:
                            retail = 0.165;
                    }
                    $("#calc").find("tbody").children("tr").each(function() {
                        let td = $(this).children("td");
                        $(td[3]).text(retail);
                        calcKoef(td);
                        calcBonus(td);
                    });
                });

                // ???????????????????????? ?????????????????? ???????????? 4 ?????????? ?? select
                $("select").on("change", function() {
                    if ($(this).closest("table").attr("id") == "calc") {
                        let td = $(this).closest("tr").children("td");
                        calcKoef(td);
                        calcBonus(td);
                    }
                });

                // ???????????????????????? ?????????????????? ???????????? ????????. 2 ??????????
                $(".line2koef").on("change", function() {
                    if ($(this).closest("table").attr("id") == "calc") {
                        calcBonus($(this).closest("tr").children("td"));
                    }
                });

                // ???????????????????????? ?????????????????? ???????????? ???????????????????? SMART ???????????? ?? ?????????????????????? ????????????
                $(".oldsmart").on("change", function() {
                    if ($(this).closest("table").attr("id") == "calc") {
                        let td = $(this).closest("tr").children("td");
                        let smart = Number($(this).val()) + Number($(td[10]).text());
                        $(td[8]).text(smart);
                        if (smart >= 10) {
                            $(td[11]).text(smart - 10);
                            $(td[2]).find("select").val(1).change();
                        } else {
                            $(td[11]).text(smart);
                            $(td[2]).find("select").val(0).change();
                        }
                    }
                });

                // ?????????????? ?????????????????? ????????????????????????
                function calcKoef(td) {
                    let sum = Number($(td[3]).text());
                    for (i = 0; i < 3; i++) {
                        sum = sum + Number($(td[i]).find("select option:selected").text());
                    }
                    $(td[4]).text(Number(sum.toFixed(2)));

                    let bonus;
                    let total = Number($(td[4]).text());
                    switch (true) {
                        case total <= 0.54:
                            bonus = 10;
                            break;
                        case total >= 0.55 && total <= 0.69:
                            bonus = 15;
                            break;
                        case total >= 0.70 && total <= 0.79:
                            bonus = 20;
                            break;
                        case total >= 0.80 && total <= 0.89:
                            bonus = 25;
                            break;
                        case total >= 0.90 && total <= 0.99:
                            bonus = 30;
                            break;
                        default:
                            bonus = 45;
                    }
                    $(td[6]).html("<input type=\"hidden\" value=\"" + bonus + "\">" + bonus);
                }

                // ???????????????????? ???????????????? ????????????
                function calcBonus(td) {
                    let total = $(td[6]).find("input").val();
                    if ($(td[5]).find("input").val() > 0) {
                        let bonus = Math.round(total * $(td[5]).find("input").val());
                        $(td[6]).html("<input type=\"hidden\" value=\"" + total + "\">" + bonus);
                    }
                }

                // ???????????? ?????????????????? ?? ???? ?????????? ajax
                function saveCalc() {
                    $(".preloader_bg, .preloader_content").fadeIn(0);
                    let calc = [];
                    $("#calc").find("tbody").children("tr").each(function() {
                        let th = $(this).children("th");
                        let td = $(this).children("td");
                        calc.push([
                            $(th[0]).attr("id"), 
                            $(td[0]).find("select").val(), 
                            $(td[1]).find("select").val(), 
                            $(td[5]).find("input").val(), 
                            $(td[9]).find("input").val(), 
                            $(td[11]).text(), 
                            $(td[13]).find("input").val(),
                            $("#retail").val()
                        ]);
                    });
                    console.log(JSON.stringify(calc));
                    $.ajax({
                        type: "POST",
                        url: "../ajax/savedata.php",
                        data:{
                            calc : JSON.stringify(calc),
                            year : $("select[name=\"year\"]").val(),
                            month : $("select[name=\"month\"]").val()
                        },
                        dataType: "json",
                        success: function(data) {
                            $(".preloader_bg, .preloader_content").fadeOut(0);
                            console.log(data);
                            alert(data.reply);
                        },
                        error: function() {
                            $(".preloader_bg, .preloader_content").fadeOut(0);
                            alert("????????????????! ???????????? ???????????????????? ????????????!");
                        }
                    });
                }
            </script>';
        }

        return;
    }

    /**
     * ???????????????????????? ?? ?????????? ?????????????????? ?????????????? ??????????????????
     * @param array $input
     * 
     * @return
     */
    public function showDetailCalc($input = []) {

        $statistics = $this->statistics;

        echo '
            <div class="b center c_ssmenu2">??????????????????</div>
            <div class="outer">
            <div class="inner">
            <table id="motiv" class ="motivation center graph">
                <thead>';
        $days_raw = '<tr><th style="text-align: left">???????? ????????????</th>';
        $indicator_raw = '<tr><th style="text-align: left">????????????????????</th>';
        foreach ($statistics['users'] as $user_id => $user) {
            if(isset($user['name'])) {
                $user_raws[$user_id] = '<tr class="center"><th style="text-align: left">'.$user['name']
                                        .($user['line'] == '2' ? ' <span style="color: red">x2</span>' : '')
                                        .'</th>';
            }
        }
        $in_work_raw = '<tr class="center"><th style="text-align: left">?????????? ?????????????????? ??????????????</th>';
        $total_raw = '<tr class="center"><th style="text-align: left">?????????? ???????????? ?? ????????????</th>';
        $percent_raw = '<tr class="center"><th style="text-align: left">?????????????? ????????????????????</th>';
        $line2_raw = '<tr class="center"><th style="text-align: left">???????????????????? ?????? ??2, %</th>';
        $line1_raw = '<tr class="center"><th style="text-align: left">???????????????????? ?????? ??1, %</th>';
        for ($day = 1; $day <= $this->last_day; $day++) {

            $days_raw .= '<th colspan="5">'.$day.'</th>';
            $indicator_raw .= '<td>??????</td><td>??????</td><td>??????????</td><td>??????????</td><td>%</td>';
            foreach ($statistics['users'] as $user_id => $user) {
                if(isset($user['name'])) {
                    $user_raws[$user_id] .= '<td>'.(isset($user[$day]['count']) ? $user[$day]['count'] : '').'</td>';
                    $user_raws[$user_id] .= '<td>'.(isset($user[$day]['time']) ? $user[$day]['time'] : '').'</td>';
                    $user_raws[$user_id] .= '<td>'.(isset($user[$day]['plan']) ? $user[$day]['plan'] : '').'</td>';
                    $user_raws[$user_id] .= '<td>'.(isset($user[$day]['smart']) && $user[$day]['smart'] > 0 ? '+' : '').'</td>';
                    $user_raws[$user_id] .= '<td '.(((isset($user[$day]['percent']) && $user[$day]['percent'] < 100)) ? 'style="background-color: #ffbfbf;"' : '').'>'.(isset($user[$day]['percent']) ? $user[$day]['percent'] : '').'</td>';
                }
            }
            $in_work_raw .= '<th colspan="5">'.$statistics['in_work'][$day].'</th>';
            $total_raw .= '<th colspan="5">'.$statistics['total'][$day].'</th>';
            $percent_raw .= '<th colspan="5">'.$statistics['percent'][$day].'</th>';
            $line2_raw .= '<td colspan="5">'.$statistics['line2'][$day].'</td>';
            $line1_raw .= '<td colspan="5">'.$statistics['line1'][$day].'</td>';

            if ($day == $this->last_day) {
                $days_raw .= '<th colspan="5">??????????</th></tr>';
                $indicator_raw .= '<td>??????</td><td>??????</td><td>??????????</td><td>??????????</td><td>%</td></tr>';
                foreach ($statistics['users'] as $user_id => $user) {
                    if(isset($user['name'])) {
                        $user_raws[$user_id] .= '<td>'.(isset($user['total']['count']) ? $user['total']['count'] : '').'</td>';
                        $user_raws[$user_id] .= '<td></td>';
                        $user_raws[$user_id] .= '<td>'.(isset($user['total']['plan']) ? $user['total']['plan'] : '').'</td>';
                        $user_raws[$user_id] .= '<td></td>';
                        $user_raws[$user_id] .= '<td>'.(isset($user['total']['percent']) ? $user['total']['percent'] : '').'</td></tr>';
                    }
                }
                $in_work_raw .= '<th colspan="5">'.$statistics['in_work']['total'].'</th></tr>';
                $total_raw .= '<th colspan="5">'.$statistics['total']['total'].'</th></tr>';
                $percent_raw .= '<th colspan="5">'.$statistics['percent']['total'].'</th></tr>';
                $line2_raw .= '<td colspan="5"></td></tr>';
                $line1_raw .= '<td colspan="5"></td></tr>';
            }
        }
        echo $days_raw;
        echo $indicator_raw;
        echo '</thead><tbody>';
        foreach ($user_raws as $user_raw) {
            echo $user_raw;
        }
        echo '</tbody><tfoot>';
        echo $in_work_raw;
        echo $total_raw;
        echo $percent_raw;
        echo $line2_raw;
        echo $line1_raw;
        echo '</tfoot>
            </table>
            </div>
            </div>';
        
        return;
    }
}