<?php
//error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

require 'Qiwi.php';
$db = new mysqli('localhost', 'donative2_donative2', 'J911577769@k', 'donative2_donative2');
header('Content-Type: application/json');
if ($_GET['method'] == 'create') {
    $amount = $_GET['amount'];
    $user_id = $_GET['user_id'];
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('GMT+5'));
    $datetime = $date->format('Y-m-d\TH:i:sP');

    $db->query("INSERT INTO `qiwi_log` (`user_id`, `amount`, `date`) VALUES('$user_id', '$amount', '$datetime')");
    $insert_id = $db->insert_id;

    $payment_url = "https://qiwi.com/payment/form/99?amountInteger={$amount}&amountFraction=0&currency=643&extra['comment']={$insert_id}&extra['account']=+998901606686";

    echo json_encode(['ok' => true, 'id' => $insert_id, 'payment_url' => $payment_url]);
} elseif ($_GET['method'] == 'check') {
    $id = $_GET['id'];
    $qiwi = new Qiwi("998901606686", "f05b0c3b3c35a2b2e93db5527ab6286b");
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone('GMT+5'));
    $datetime = $date->format('Y-m-d\TH:i:sP');
    $info = $db->query("SELECT * FROM `qiwi_log` WHERE `id` = '$id'")->fetch_assoc();
    $data = $qiwi->getPaymentsHistory([
        'startDate' => $info['date'],
        'endDate' => $datetime,
        'rows' => '50',
        'operation' => 'IN',
    ]);

    foreach ($data['data'] as $value) {
        if (empty($value['comment'])) continue;
        if ($value['comment'] == $id) {
            if ($value['sum']['amount'] == $info['amount']) {
                $db->query("UPDATE `qiwi_log` SET `is_completed` = '1' WHERE `id` = '$id'");
                $db->query("UPDATE `bot_users` SET `modeys` = `modeys` + '{$info['amount']}' WHERE `user_id` = '{$info['user_id']}'");
                echo json_encode(['ok' => true, 'status' => 'success']);
                exit;
            } else {
                echo json_encode(['ok' => false, 'status' => 'error']);
                exit;
            }
        } else {
            continue;
        }
    }

    echo json_encode(['ok' => false, 'status' => 'error']);
}
