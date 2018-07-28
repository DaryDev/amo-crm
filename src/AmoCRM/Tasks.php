<?php

namespace AmoCRM\Tasks;

use function \cli\line;
use function AmoCRM\Curl\sendRequest;
use function AmoCRM\Helper\getSubdomain;

/**
 * Тип задач у сделок.
 * @var int
 */
const TYPE_LEAD = 2;

/**
 * Создание задачи для сделок без открытых задач.
 *
 * @return void
 */
function run()
{
    // Получаем все задачи.
    $allTasks = getAllTasksFromAmoCRM();
    // Среди них находим такие, которые без открытых задач.
    $leadIdsWithoutOpenedTasks = getLeadIdsWithoutOpenedTasks($allTasks);

    // Получаем id сделок по которым есть задачи.
    $leadIdsWithTasks = array_filter($allTasks, function ($task) {
        return $task['element_type'] === TYPE_LEAD;
    });
    $leadIdsWithTasks = array_unique(array_column($leadIdsWithTasks, 'element_id'));
    // Получаем все сделки.
    $allLeads = getAllLeadsFromAmoCRM();
    $allLeadIds = array_unique(array_column($allLeads, 'id'));

    // Получаем сделки по которым не было создано ни одной задачи.
    $leadsWithoutAnyTasks = array_diff($allLeadIds, $leadIdsWithTasks);
    $leadsToCreateTask = array_merge($leadIdsWithoutOpenedTasks, $leadsWithoutAnyTasks);
    if (count($leadsToCreateTask) > 0) {
        createTasks($leadsToCreateTask);
        line(count($leadsToCreateTask) . ' задач(а) созданы(а) успешно ');
    } else {
        line('Нет сделок без открытых задач');
    }
}

/**
 * Получить id сделок без открытых задач.
 *
 * @param array $response Данные задач из респонса.
 *
 * @return array Массив с id сделок.
 */
function getLeadIdsWithoutOpenedTasks(array $response)
{
    $groupedByLeads = [];
    foreach ($response as $item) {
        if ($item['element_type'] !== TYPE_LEAD) {
            continue;
        }
        if (!array_key_exists($item['element_id'], $groupedByLeads)) {
            $groupedByLeads[$item['element_id']] = [];
        }
        // По каждой сделке храним массив результатов всех задач.
        $groupedByLeads[$item['element_id']][] = isset($item['is_completed']) ? $item['is_completed'] : 0;
    }
    $leadIds = [];
    foreach ($groupedByLeads as $leadId => $isCompletedArray) {
        // Нас интересуют сделки по которым все задачи завершены.
        if (count($isCompletedArray) == array_sum($isCompletedArray)) {
            $leadIds[] = $leadId;
        }
    }
    return $leadIds;
}

/**
 * Получает данные всех задач по API задач.
 *
 * @return array
 */
function getAllTasksFromAmoCRM()
{
    $subdomain = getSubdomain();
    $params = [
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/tasks/',
    ];
    $response = sendRequest($params);
    $response = !empty($response['_embedded']['items']) ? $response['_embedded']['items'] : [];

    return $response;
}

/**
 * Получает данные всех сделок по API сделок.
 *
 * @return array
 */
function getAllLeadsFromAmoCRM()
{
    $subdomain = getSubdomain();
    $params = [
        CURLOPT_URL => 'https://' . $subdomain . '.amocrm.ru/api/v2/leads',
    ];
    $response = sendRequest($params);
    $response = !empty($response['_embedded']['items']) ? $response['_embedded']['items'] : [];

    return $response;
}

/**
 * Создание задач по сделкам без открытых задач.
 *
 * @param array $leadIds Id сделок.
 *
 * @return void
 */
function createTasks(array $leadIds)
{
    $data = ['add' => []];
    foreach ($leadIds as $leadId) {
        $data['add'][] = [
            'element_id'       => $leadId,
            'element_type'     => TYPE_LEAD,
            'task_type'        => 1,
            'text'             => 'Сделка без задачи',
            'complete_till_at' => strtotime("+1 week")
        ];
    }
    $subdomain = getSubdomain();
    $curlParams = [
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_URL           => 'https://' . $subdomain . '.amocrm.ru/api/v2/tasks?type=json',
        CURLOPT_POSTFIELDS    => json_encode($data)
    ];
    sendRequest($curlParams);
}
