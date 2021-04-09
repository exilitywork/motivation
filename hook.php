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

/**
 * Install the plugin
 *
 * @return boolean
 */
function plugin_motivation_install() {
    global $DB;

    if (!$DB->tableExists('glpi_plugin_motivation_graphs')) {
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_motivation_graphs`
            (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `user_id` INT(11) NOT NULL,
                `year` INT(11) NOT NULL,
                `month` INT(11) NOT NULL,
                `line` INT(11) NOT NULL,
                `graph` VARCHAR(35) NOT NULL,
                `plan` BOOL,
                `penalty` BOOL,
                `retail` INT(11),
                `line2koef` FLOAT(2, 1),
                `notification` TEXT,
                `oldsmart` INT(11),
                `newsmart` INT(11),
                PRIMARY KEY (`id`),
                KEY (`user_id`)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
        ";
        $DB->query($create_table_query) or die($DB->error());
    }

    if (!$DB->tableExists('glpi_plugin_motivation_codes')) {
        $create_table_query = "
            CREATE TABLE IF NOT EXISTS `glpi_plugin_motivation_codes`
            (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `code` INT(11) NOT NULL,
                `year` INT(11) NOT NULL,
                `month` INT(11) NOT NULL,
                `time_begin` VARCHAR(5) NOT NULL,
                `time_end` VARCHAR(5) NOT NULL,
                `dinner_begin` VARCHAR(5) NOT NULL,
                `dinner_end` VARCHAR(5) NOT NULL,
                PRIMARY KEY (`id`)
            ) ENGINE = InnoDB DEFAULT CHARSET = utf8 COLLATE = utf8_unicode_ci;
        ";
        $DB->query($create_table_query) or die($DB->error());
    }

    return true;
}

/**
 * Uninstall the plugin
 *
 * @return boolean
 */
function plugin_motivation_uninstall() {
    global $DB;

    $drop_table = "DROP TABLE glpi_plugin_motivation_graphs";
    //$DB->query($drop_table);

    $drop_table = "DROP TABLE glpi_plugin_motivation_codes";
    //$DB->query($drop_table);
    
    return true;
}

