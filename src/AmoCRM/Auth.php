<?php

namespace AmoCRM\Auth;

use function \cli\line;
use function \cli\prompt;
use function AmoCRM\Curl\sendRequest;

/**
 * Отправка запроса авторизации.
 *
 * @return void
 */
function run()
{
    $userLogin = prompt('Your email');
    $userHash  = prompt('Your API hash');
    $subdomain  = prompt('Your subdomain');
    // Храним в файлe поддомен для использования в последующих запросах.
    file_put_contents('src/storage.txt', $subdomain);
    $postData = [
        'USER_LOGIN' => $userLogin,
        'USER_HASH'  => $userHash
    ];

    $params = [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_URL           => 'https://' . $subdomain . '.amocrm.ru/private/api/auth.php?type=json',
        CURLOPT_POSTFIELDS    => json_encode($postData)
    ];
    $response = sendRequest($params);
    $response = $response['response'];
    if (!isset($response['auth'])) {
        line('Авторизация не удалась');
        exit;
    }
    line('Запрос авторизации прошел успешно');
}
