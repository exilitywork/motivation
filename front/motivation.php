<?php

global $DB;
include ("../../../inc/includes.php");
include ("/var/www/glpi/plugins/motivation/inc/SimpleXLSX.php");

//Session::checkRight('entity', READ);

Html::header("Мотивация");
/*echo '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.24/css/jquery.dataTables.min.css"/>
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/buttons/1.7.0/css/buttons.dataTables.min.css"/>';*/
 
echo '<script type="text/javascript" src="https://cdn.datatables.net/v/dt/dt-1.10.24/datatables.min.js"></script>';

$file_graph = '/var/www/glpi/plugins/motivation/front/sap.xlsx';
$file_codes = '/var/www/glpi/plugins/motivation/front/code.xlsx';
$uploaddir = '/var/www/glpi/plugins/motivation/front/';
$options_month['value'] = date('n');
//$options_month['on_change'] = 'submit()';
$options_year['value'] = date('Y');
//$options_year['on_change'] = 'submit()';
$codes = [];
$lines = [
    '00022931' => 2,
    '00023369' => 2,
    '00023732' => 2,
    '00023125' => 0,
    '00023654' => 0
];
$days = date('j');
//$days = 10;

//print_r($_POST);
//print_r((strtotime(str_replace('-',':','17-30')) - strtotime(str_replace('-',':','7-00')))/60);

if (isset($_POST['month'])) {
    $options_month['value'] = $_POST['month'];
}
if (isset($_POST['year'])) {
    $options_year['value'] = $_POST['year'];
}
if (isset($_FILES['graph_file']['tmp_name'])) {
    if (!(move_uploaded_file($_FILES['graph_file']['tmp_name'], $file_graph))) {
        die("Ошибка при загрузке графика!\n");
    }
}
if (isset($_FILES['code_file']['tmp_name'])) {
    if (!(move_uploaded_file($_FILES['code_file']['tmp_name'], $file_codes))) {
        die("Ошибка при загрузке кодов!\n");
    }
}

foreach ($_POST as $param => $value) {
    //print_r(strpos($param, "tab"));
    if (strpos($param, "tab") !== false) {
        //print_r($param." --- ".$value."\n");
        $lines[substr($param, 3)] = $value;
    }
}

//print_r($lines);

foreach(range(date('Y'), 2021) as $year) {
    $years[$year] = $year;
}

/*$uploaddir = '/var/www/glpi/plugins/motivation/front/';
$uploadfile = $uploaddir . basename($_FILES['graph_file']['name']);

if (move_uploaded_file($_FILES['graph_file']['tmp_name'], $uploadfile)) {
    echo "Файл корректен и был успешно загружен.\n";
} else {
    echo "Возможная атака с помощью файловой загрузки!\n";
}*/

echo "<form name='form' method='post' action='motivation.php' enctype='multipart/form-data' >";
echo "<div id='searchcriteria' class='center'>";
echo "<h4>Расчетный месяц и год:</h4>";
Dropdown::showFromArray("month", Toolbox::getMonthsOfYearArray(), $options_month);
Dropdown::showFromArray("year", $years, $options_year);
//echo getUserName(22);
echo "<h4>Загрузка графика работы и кодов к нему</h4>";
echo "График: <input type='file' name='graph_file'>&nbsp;";
echo "Коды: <input type='file' name='code_file'>&nbsp;";
//echo "<input type='hidden' name='action' value='preview_import'>";
echo "<input type='submit' name='import' value=\""._sx('button', 'Загрузить')."\" class='submit'>";

// Close for Form
echo "</div>";
Html::closeForm();



/*if ( $xlsx = SimpleXLSX::parse('/var/www/glpi/plugins/motivation/front/sap.xlsx') ) {
	echo $xlsx->toHTML();
  } else {
    echo SimpleXLSX::parseError();
 }*/

echo "<form name='form' method='post' action='motivation.php' enctype='multipart/form-data' >";

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>ТАБЛИЦА КОДОВ
echo('<div class="b center c_ssmenu2" style="margin: 10px">Таблица кодов</div>');
echo('<table id="codes" class="graph codes"><thead><tr>');
if ( $xlsx = SimpleXLSX::parse($file_codes)) {
    // Produce array keys from the array values of 1st array element
    $header_values = $rows = [];
    foreach ( $xlsx->rows() as $key => $raw ) {
        if ($key > 0) {
            if ( $key === 1 ) {
                echo '<th>'.$raw[0].'</th><th colspan="2">'.$raw[1].'</th><th colspan="2">'.$raw[3].'</th><th>Фактическое время</th></tr></thead><tbody>';
                //echo '<th>'.$raw[0].'</th><th>'.$raw[1].'</th><th></th><th>'.$raw[3].'</th><th></th><th>Фактическое время</th></tr></thead><tbody>';
                //unset($r[0]);
                //$header_values = $raw;
                continue;
            }
            $dinner = ((strtotime(str_replace('-',':',$raw[4])) - strtotime(str_replace('-',':',$raw[3]))) / 60);
            $codes[$key - 1]['time'] = ((strtotime(str_replace('-',':',$raw[2])) - strtotime(str_replace('-',':',$raw[1]))) / 60 - $dinner);
            switch ($codes[$key - 1]['time']) {
                case '600': $codes[$key - 1]['norm'] = 1; break;
                case '540': $codes[$key - 1]['norm'] = 0.9; break;
                case '480': $codes[$key - 1]['norm'] = 0.8; break;
                case '420': $codes[$key - 1]['norm'] = 0.7; break;
                case '300': $codes[$key - 1]['norm'] = 0.5; break;
                case '240': $codes[$key - 1]['norm'] = 0.4; break;
                default: $codes[$key - 1]['norm'] = 1;
            }
            //$r[0] = getUserName($r[0]);
            echo '<tr><td>';
            echo implode('</td><td>', $raw);
            echo '<td>'.$codes[$key - 1]['time'].' мин.</td>';
            echo '</td></tr>';
            //$tab_num = $r[0];
            //unset($r[0]);
            //$rows[$key] = array_combine( $header_values, $r );
        }
    }
    //print_r( $codes );
} else {
    die('Нет файла кодов!');
}
echo ('</tbody></table>');
//print_r($codes);

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> ГРАФИК
echo('<div class="b center c_ssmenu2">График</div>');
//echo('<table class="tab_cadre_fixehov"><tr><th>');
echo('<table id="graph" class="center graph"><tr><th>Линия</th><th>');
if ( $xlsx = SimpleXLSX::parse($file_graph)) {
// Produce array keys from the array values of 1st array element
    $header_values = $rows = [];
    foreach ( $xlsx->rows() as $key => $raw ) {
        if ( $key === 0 ) {
            echo implode('</th><th>', $raw);
            echo('</th></tr>');
            //unset($raw[0]);
            //$header_values = $raw;
            continue;
        }
        $tab_num = str_pad($raw[0], 8, "0", STR_PAD_LEFT);
        $iterator = $DB->request([
            'SELECT' => ['id','realname', 'firstname'],
            'FROM' => 'glpi_users',
            'WHERE' => ['registration_number' => $tab_num],
            'LIMIT' => 1]
        );
        if (count($iterator)) {
            while ($data = $iterator->next()) {
                $user_id = $data['id'];
                $statistics['users'][$user_id]['name'] = $data['realname'].' '.$data['firstname'];
                $statistics['users'][$user_id]['line'] = isset($lines[$tab_num]) ? $lines[$tab_num] : 1;
            }
        }
        //$r[0] = getUserName($r[0]);
        echo '<tr class="tab_bg_2 center"><td>'.Dropdown::showFromArray('tab'.$tab_num, [0, 1, 2], 
                                                                            ['value' => $statistics['users'][$user_id]['line'], 
                                                                            'display_emptychoice' => true, 
                                                                            'display' => false]
                                                                        ).'</td><th style="text-align: left">'.$statistics['users'][$user_id]['name'].'</th><td>';
        unset($raw['0']);

        foreach ($raw as $day => $code) {
            if ($code){
                $statistics['users'][$user_id][$day]['time'] = $codes[$code]['time'];
                $statistics['users'][$user_id][$day]['plan'] = $codes[$code]['norm'];
            }
        }
        echo implode('</td><td>', $raw);
        echo('</td></tr>');
        //$tab_num = $r[0];
        //unset($r[0]);
        //$rows[$tab_num] = array_combine( $header_values, $r );
    }
    //print_r( $rows );
} else {
    die('Нет файла графика!');
}
echo "</table>";
echo "<div class='center'><input type='submit' name='calc' value=\""._sx('button', 'Рассчитать')."\" class='submit'>";

// Close for Form
echo "</div>";
Html::closeForm();

if ($options_month['value'] == date('n') && $options_year['value'] == date('Y')) {
    //$statistic = getTicketCount($statistics, $options_year['value'], $options_month['value'], date('j'));
    //$statistic = getLinesPercent($statistics, date('j'));
    //$statistic = getPlanPercent($statistics, date('j'));
    $statistics = getTicketCount($statistics, $options_year['value'], $options_month['value'], $days);
    $statistics = getLinesPercent($statistics, $days);
    $statistics = getPlanPercent($statistics, $days);
} else {
    $statistics = getTicketCount($statistics, $options_year['value'], $options_month['value'], cal_days_in_month(CAL_GREGORIAN, $options_month['value'], $options_year['value']));
    $statistics = getLinesPercent($statistics, cal_days_in_month(CAL_GREGORIAN, $options_month['value'], $options_year['value']));
    $statistics = getPlanPercent($statistics, cal_days_in_month(CAL_GREGORIAN, $options_month['value'], $options_year['value']));
}
//$statistics = getTicketCount($statistics);
//$statistics = getLinesPercent($statistics);
//$statistics = getPlanPercent($statistics);
//print_r($statistics['total']);
//echo '<table class ="center graph">';

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> ИТОГИ
echo('<div class="b center c_ssmenu2">Расчет итогового коэффициента</div>');

echo('<div class="center">Выполнение плана по розничному товарообороту: <input id="retail" type="number" min="0" step="1" style="width: 3em;">%</div>');

echo('<table id="calc" class="center graph">');
echo('<thead><tr>');
echo('<th>Сотрудник</th><th>План</th><th>Нарекания</th><th>SMART</th><th>Розница</th><th>Итоговый</th><th>Коэф. 2 линии</th><th>Прем. часть</th><th></th><th>SMART кол-во</th><th>С пред. месяца</th><th>Сделано</th><th>На след. месяц</th>');
echo('</tr></thead>');
echo('<tbody>');
foreach ($statistics['users'] as $user_id => $user) {
    if(isset($user['name'])) {
        if (isset($user['total']['smart_task']) && isset($user['total']['smart_promo'])) {
            $smart = ($user['total']['smart_promo'] < 10) ? $user['total']['smart_task'] + $user['total']['smart_promo'] : $user['total']['smart_task'];
        } else {
            $smart = 0;
        }
        $plan_сoef = $user['total']['percent'] >= 100 ? 0.4 : 0;
        $smart_сoef = $smart >= 10 ? 0.2 : 0;
        $total_сoef = $plan_сoef + 0.25 + $smart_сoef + 0.15;
        echo '<tr class="center">
                <th style="text-align: left">'.$user['name'].'</th>
                <td>'.Dropdown::showFromArray('plan_'.$user_id, ['0', '0.4'], ['value' => ($plan_сoef ? 1 : 0), 'display' => false]).'</td>
                <td>'.Dropdown::showFromArray('penalty_'.$user_id, ['0', '0.25'], ['value' => 1, 'display' => false]).'</td>
                <td>'.Dropdown::showFromArray('smart_'.$user_id, ['0', '0.2'], ['value' => ($smart_сoef ? 1 : 0), 'display' => false]).'</td>
                <td>0.09</td>
                <td>'.$total_сoef.'</td>
                <td><input type="number" min="1" step="0.1" class="line2koef" style="width: 5em;"></td>
                <td>'.($user['total']['percent'] >= 100 ? 65 : 20).'</td>
                <td></td>
                <td>'.$smart.'</td>
                <td><input type="number" min="0" step="1" class="oldsmart" style="width: 5em;"></td>
                <td>'.$smart.'</td>
                <td>'.($smart >= 10 ? $smart - 10 : $smart).'</td>
        </tr>';
        //$user_raws[$user_id] = '<tr class="center"><th style="text-align: left">'.$user['name'].'</th><tr>';
    }
}
echo('</tbody>');
echo('</table>');

echo('<script>

    // изменение процента выполнения плана розницы
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

    // отслеживание изменения первых 4 ячеек с select
    $("select").on("change", function() {
        if ($(this).closest("table").attr("id") == "calc") {
            let td = $(this).closest("tr").children("td");
            calcKoef(td);
            calcBonus(td);
        }
    });

    // отслеживание изменения ячейки Коэф. 2 линии
    $(".line2koef").on("change", function() {
        if ($(this).closest("table").attr("id") == "calc") {
            calcBonus($(this).closest("tr").children("td"));
        }
    });

    // отслеживание изменения ячейки количества SMART заявок с предыдущего месяца
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

    // подсчет итогового коэффициента
    function calcKoef(td) {
        let sum = Number($(td[3]).text());
        for (i = 0; i < 3; i++) {
            sum = sum + Number($(td[i]).find("select option:selected").text());
        }
        $(td[4]).text(Number(sum.toFixed(2)));
    }

    // вычисление процента премии
    function calcBonus(td) {
        let bonus;
        let total = Number($(td[4]).text());
        if ($(td[5]).find("input").val() > 0) {
            total = total * $(td[5]).find("input").val();
        }
        switch (true) {
            case total <= 0.54:
                bonus = 10;
                break;
            case total >= 0.55 && total <= 0.69:
                bonus = 20;
                break;
            case total >= 0.70 && total <= 0.79:
                bonus = 30;
                break;
            case total >= 0.80 && total <= 0.89:
                bonus = 40;
                break;
            case total >= 0.90 && total <= 0.99:
                bonus = 50;
                break;
            default:
                bonus = 65;
        }
        $(td[6]).text(bonus);
    }
</script>');

// >>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>> МОТИВАЦИЯ С ПРОКРУТКОЙ
echo '<div class="b center c_ssmenu2">Мотивация</div>';
echo '<div class="outer">';
echo '<div class="inner"><table id="motiv" class ="motivation center graph"><thead>';
$days_raw = '<tr><th style="text-align: left">День месяца</th>';
$indicator_raw = '<tr><th style="text-align: left">Показатель</th>';
foreach ($statistics['users'] as $user_id => $user) {
    if(isset($user['name'])) {
        $user_raws[$user_id] = '<tr class="center"><th style="text-align: left">'.$user['name'].($user['line'] == '2' ? ' <span style="color: red">x2</span>' : '').'</th>';
    }
}
$in_work_raw = '<tr class="center"><th style="text-align: left">Всего выполнено группой</th>';
$total_raw = '<tr class="center"><th style="text-align: left">Всего подано в группу</th>';
$percent_raw = '<tr class="center"><th style="text-align: left">Процент выполнения</th>';
$line2_raw = '<tr class="center"><th style="text-align: left">Показатель для х2, %</th>';
$line1_raw = '<tr class="center"><th style="text-align: left">Показатель для х1, %</th>';
for ($day = 1; $day <= $days; $day++) {

    $days_raw .= '<th colspan="5">'.$day.'</th>';
    $indicator_raw .= '<td>кол</td><td>мин</td><td>норма</td><td>смарт</td><td>%</td>';
    foreach ($statistics['users'] as $user_id => $user) {
        if(isset($user['name'])) {
            $user_raws[$user_id] .= '<td>'.(isset($user[$day]['count']) ? $user[$day]['count'] : '').'</td>';
            $user_raws[$user_id] .= '<td>'.(isset($user[$day]['time']) ? $user[$day]['time'] : '').'</td>';
            $user_raws[$user_id] .= '<td>'.(isset($user[$day]['plan']) ? $user[$day]['plan'] : '').'</td>';
            $user_raws[$user_id] .= '<td>'.(isset($user[$day]['smart']) && $user[$day]['smart'] > 0 ? '+' : '').'</td>';
            $user_raws[$user_id] .= '<td>'.(isset($user[$day]['percent']) ? $user[$day]['percent'] : '').'</td>';
        }
    }
    $in_work_raw .= '<th colspan="5">'.$statistics['in_work'][$day].'</th>';
    $total_raw .= '<th colspan="5">'.$statistics['total'][$day].'</th>';
    $percent_raw .= '<th colspan="5">'.$statistics['percent'][$day].'</th>';
    $line2_raw .= '<td colspan="5">'.$statistics['line2'][$day].'</td>';
    $line1_raw .= '<td colspan="5">'.$statistics['line1'][$day].'</td>';

    if ($day == $days) {
        $days_raw .= '<th colspan="5">Итого</th></tr>';
        $indicator_raw .= '<td>кол</td><td>мин</td><td>норма</td><td>смарт</td><td>%</td></tr>';
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
echo '</tfoot></table>';
echo '</div>';
echo '</div>';

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

function getTicketCount($statistics = [], $year = '2021', $month = '03', $days = '10', $group_id = '33') {
    global $DB;
    //str_pad($options_month['value'], 2, "0", STR_PAD_LEFT);
    //echo $day;
    $statistics['total']['total'] = 0;
    $statistics['in_work']['total'] = 0;
    $month = str_pad($month, 2, '0', STR_PAD_LEFT);
    for ($day = 1; $day <= $days; $day++) {
        //$day = str_pad($day, 2, '0', STR_PAD_LEFT);
        $sql_date = "'".$year."-".$month."-".str_pad($day, 2, '0', STR_PAD_LEFT)." 00:00:00' AND '".$year."-".$month."-".str_pad($day, 2, '0', STR_PAD_LEFT)." 23:59:59'";

        $total = $DB->result($DB->query("
            SELECT count(*) AS total
            FROM glpi_groups_tickets, glpi_tickets, glpi_groups
            WHERE glpi_groups_tickets.groups_id = ".$group_id."
                AND glpi_groups_tickets.groups_id = glpi_groups.id
                AND glpi_groups_tickets.tickets_id = glpi_tickets.id
                AND glpi_tickets.is_deleted = 0
                AND glpi_tickets.date BETWEEN ".$sql_date), 0, 'total');
        
        /*$smart_count = $DB->result($DB->query("
            SELECT count(*) AS total
            FROM glpi_groups_tickets, glpi_tickets, glpi_groups
            WHERE glpi_groups_tickets.groups_id = ".$group_id."
                AND glpi_groups_tickets.groups_id = glpi_groups.id
                AND glpi_groups_tickets.tickets_id = glpi_tickets.id
                AND glpi_tickets.is_deleted = 0
                AND glpi_tickets.requesttypes_id IN (9, 10)
                AND glpi_tickets.date BETWEEN ".$sql_date), 0, 'total');*/

        $stat_query = $DB->query("
            SELECT glpi_groups_users.users_id AS id, glpi_users.firstname AS name ,glpi_users.realname AS sname, count(glpi_tickets_users.id) AS count
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
        $statistics['in_work'][$day] = 0;
        while ($stat_row = $DB->fetch_assoc($stat_query)) {
            $statistics['users'][$stat_row['id']][$day]['count'] = $stat_row['count'];
            $statistics['in_work'][$day] += $stat_row['count'];
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
            $statistics['users'][$smart_row['id']][$day]['smart'] = $smart_row['smart_task'] + $smart_row['smart_promo'];
            $statistics['users'][$smart_row['id']]['total']['smart'] = isset($statistics['users'][$smart_row['id']]['total']['smart']) 
                                                                        ? $statistics['users'][$smart_row['id']]['total']['smart'] + $statistics['users'][$smart_row['id']][$day]['smart']
                                                                        : $statistics['users'][$smart_row['id']][$day]['smart'];
            //$statistics['in_work'][$day] = $statistics['in_work'][$day] + $stat_row['count'];
        }
        $statistics['percent'][$day] = round($statistics['in_work'][$day] / $statistics['total'][$day] * 100, 2);
        $statistics['total']['total'] += $total;
        $statistics['in_work']['total'] += $statistics['in_work'][$day];
        $statistics['percent']['total'] = round($statistics['in_work']['total'] / $statistics['total']['total'] * 100, 2);

    }
    //print_r($statistics['users']);
    return $statistics;
}

function getLinesPercent($statistics, $days = 10) {
    for ($day = 1; $day <= $days; $day++) {
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
            //print_r($day.'/');
            $statistics['line1'][$day] = $statistics['line2'][$day] = round(100 / ($line1 + $line2), 2);
        }
    }

    return $statistics;
}

function getPlanPercent($statistics, $days = 10) {
    $user['total']['plan'] = 0;
    for ($day = 1; $day <= $days; $day++) {
        //$line1 = $line2 = 0;
        foreach ($statistics['users'] as $id => $user) {
            if (isset($user[$day]['time']) && $user['line'] == 0) {
                $user[$day]['plan'] = '';
                //$statistics['users'][$id] = $user;
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
                //$statistics['users'][$id] = $user;
            }
            if (isset($user[$day]['plan']) && $user[$day]['plan'] != '') {
                $plan = $user[$day]['plan'];
            } else {
                $plan = 0;
            }
            /*if ($id == 1031) {
                print_r('--'.$plan);
            }*/
            $user['total']['plan'] = isset($user['total']['plan']) ? $user['total']['plan'] + $plan : $plan;
            if ($day == $days && isset($user['line']) && $user['line'] > 0) {
                $user['total']['percent'] = round($user['total']['count'] / $user['total']['plan'] * 100);
            } else {
                $user['total']['percent'] = 100;
            }
            $statistics['users'][$id] = $user;
        }
    }
    return $statistics;
}

//echo '<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"></script>';