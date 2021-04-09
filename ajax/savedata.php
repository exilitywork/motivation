<?php
/**
 * -------------------------------------------------------------------------
 * Motivation plugin for GLPI
 * Copyright (C) 2021 by the Belwest, Kapeshko Oleg.
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Motivation plugin.
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

if (!stripos($_SERVER['HTTP_REFERER'], '/plugins/motivation/front/motivation.php')) {
    die("Sorry. You can't access directly to this file");
}

$data = json_decode($_POST['calc']);

include ("../../../inc/includes.php");

$result = PluginMotivationMotivation::saveData($data, $_POST['year'], $_POST['month']);
if (is_array($result)) {
    $names = [];
    foreach ($result as $id => $val) {
        array_push($names, getUserName($id));
    }
    print '{"reply": "Ошибка при сохранении данных пользователей: '.implode(', ', $names).'!"}';
} else {
    if ($result) {
        print '{"reply": "Данные сохранены!"}';
    } else {
        print '{"reply": "Ошибка: некорректные данные от клиента!"}';
    }
}
?>