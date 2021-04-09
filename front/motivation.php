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

global $DB;
include ("../../../inc/includes.php");

//Session::checkRight('entity', READ);

Html::header("Мотивация");
/*echo '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css"/>';*/
 
echo '<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/datatables.min.js"></script>';

$uploaddir = '/var/www/glpi/plugins/motivation/front/';
$options_month['value'] = date('n');
$options_month['on_change'] = 'submit()';
$options_year['value'] = date('Y');
$options_year['on_change'] = 'submit()';
if (isset($_POST['month'])) {
    $options_month['value'] = $_POST['month'];
}
if (isset($_POST['year'])) {
    $options_year['value'] = $_POST['year'];
}

foreach(range(date('Y'), 2021) as $year) {
    $years[$year] = $year;
}

echo "
    <form name='form' method='post' action='motivation.php' enctype='multipart/form-data'>
        <div id='searchcriteria' class='center'>
            <h4>Расчетный месяц и год:</h4>";
Dropdown::showFromArray("month", Toolbox::getMonthsOfYearArray(), $options_month);
Dropdown::showFromArray("year", $years, $options_year);
echo "<h4>Загрузка графика работы и кодов к нему</h4>
        График: <input type='file' name='graph_file' accept='.xlsx'>&nbsp;
        Коды: <input type='file' name='code_file' accept='.xlsx'>&nbsp;
        <input type='submit' name='import' value=\""._sx('button', 'Загрузить')."\" class='submit'>
    </div>";
Html::closeForm();

$motivation = new PluginMotivationMotivation();

if (!empty($_POST)) {
    $motivation->loadData($_POST, $_FILES);
}
$motivation->initCalc($options_year['value'], $options_month['value']);
$motivation->showCodes();
$motivation->showGraphs();
$motivation->showCalc();
$motivation->showDetailCalc();

/*echo "
<script>
jQuery( document ).ready(function( $ ) {
    $('#motiv').DataTable( {
        dom: 'Bfrtip',
        buttons: [
            'excelHtml5',
            {
                extend: 'pdfHtml5',
                text: 'Save current page',
                exportOptions: {
                    modifier: {
                        page: 'current'
                    }
                }
            }
        ]
    } );
});</script>";


echo '<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/2.5.0/jszip.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/pdfmake.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.36/vfs_fonts.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
<script type="text/javascript" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>';*/

Html::footer();

//echo '<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>';