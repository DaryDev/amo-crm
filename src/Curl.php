<?php

namespace AmoCRM\Curl;

use function \cli\line;

/**
 * Отправка запроса на API.
 *
 * @param array $params Параметры curl.
 *
 * @return array Ответ на запрос.
 */
function sendRequest(array $params = [])
{
    $curl = curl_init();

    foreach ($params as $curlParamName => $curlParamValue) {
        curl_setopt($curl, $curlParamName, $curlParamValue);
    }

    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_USERAGENT, 'amoCRM-API-client/1.0');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_COOKIEFILE, __DIR__ . '/cookie.txt');
    curl_setopt($curl, CURLOPT_COOKIEJAR, __DIR__ . '/cookie.txt');
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

    $out = curl_exec($curl);
    try {
        checkResponse($curl);
    } catch (\Exception $e) {
        line('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        exit;
    }
    curl_close($curl); #Завершаем сеанс cURL

    return json_decode($out, true);
}

/**
 * Проверка корректности полученного ответа.
 *
 * @param $curl
 *
 * @throws \Exception Выбрасываем исключение в случае некорректного ответа.
 */
function checkResponse($curl)
{
    $code = curl_getinfo($curl, CURLINFO_HTTP_CODE); #Получим HTTP-код ответа сервера
    $code = (int)$code;
    $errors = [
        301 => 'Moved permanently',
        400 => 'Bad request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not found',
        500 => 'Internal server error',
        502 => 'Bad gateway',
        503 => 'Service unavailable'
    ];
    if ($code != 200 && $code != 204) {
        throw new \Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
    }
}
