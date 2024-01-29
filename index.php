<?php
include('_header.php');
if (users_check_auth_rpd(ACC_EVT_PASSPORT) == false)
    exit();

//0 - протокол итогов
//1 - итоговый протокол
$p_type = isset($_GET['p_type']) ? intval($_GET['p_type']) : 0;

if (!empty($_FILES))
{
    $template = $_FILES['file']['tmp_name'];
    $to_dir = $p_type  == 0 ? "/mnt/data_user/word/protocol_itogov/{$evt_id}/" : "/mnt/data_user/word/itogoviy_protocol/{$evt_id}/";
    @mkdir($to_dir);
    $file_name = "{$evt_id}_template.docx";
    copy($template, $to_dir.$file_name);
}

// Готовим данные. Вот лучше с этим не играть. Пусть будет отдельное мероприятие для сбора общего
$federal_sql_filter     = $evt_is_federal ? "evt_fed_group = {$evt_fed_group}"  : '';
$evt_sql_filter         = $evt_is_federal ? '' : "f.evt_id = {$evt_id}";
$rows                   = get_all_data2($evt_sql_filter, $federal_sql_filter);

$filtered_rows          = filter_data($rows);
$result_array           = get_final_data($filtered_rows);

//var_dump($rows);
//var_dump($filtered_rows);
//var_dump($result_array);
//exit();

//Заявленные  (Из протокола НОМЕР 1, которого пока нет)
//$declared_sign_cnt  = $evt_is_federal ? get_declared_sign_cnt_fed($evt_fed_group) : get_declared_sign_cnt($evt_id);
$declared_sign_cnt  = '__'; //Без АППЛ

//Представленные 
$offered_sign_cnt   = $evt_is_federal ? get_declared_sign_cnt_fed($evt_fed_group) : get_declared_sign_cnt($evt_id);
//$offered_sign_cnt   = '__'; //Без АППЛ

//Провереные подписи
$done_count         = $evt_is_federal ? get_done_sign_cnt_fed($evt_fed_group) : get_done_sign_cnt($evt_id);

//Недействительные 
$bad_sign_count     = get_bad_sign_cnt($result_array);
$bad_expert_sign_count = get_expert_bad_sign_cnt($result_array);
$bad_passport_sign_count = get_passport_bad_sign_cnt($result_array);

//60k и 90k
$evt_sample_count = $evt_sample == 1 ? $evt_sign_limit_lower * $evt_sample_1_percent / 100 : $evt_sign_limit_lower * ($evt_sample_2_percent + $evt_sample_1_percent) / 100; 
$bad_percent = round(1 * ($bad_sign_count / $evt_sample_count), 5) * 100;
$p_type = $bad_percent <= 5.0 ? 1 : $p_type; //Если нарушений меньше порога, то выводим только Итоговый протокол

//Действительные 
$good_sign_count    = $offered_sign_cnt  - $bad_sign_count;
$good_sign_count    = $good_sign_count < 0 ? 0 : $good_sign_count; //не может быть < 0
//$good_sign_count    = '__'; //Без АППЛ
