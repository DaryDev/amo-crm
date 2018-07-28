<?php

namespace AmoCRM\Helper;

use function \cli\line;

/**
 * Получение поддомена аккаунта.
 *
 * @return bool|string
 */
function getSubdomain()
{
    $subdomain = file_get_contents('../storage.txt');
    if (empty($subdomain)) {
        line('Сперва авторизуйтесь');
        exit;
    }
    return $subdomain;
}
