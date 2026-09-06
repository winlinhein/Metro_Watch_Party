<?php

function jsonRespondAndContinue(array $payload): void
{
    $json = json_encode($payload);
    if (!headers_sent()) {
        header('Content-Type: application/json');
        header('Content-Length: ' . strlen($json));
        header('Connection: close');
    }
    echo $json;
    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
        return;
    }
    while (ob_get_level() > 0) {
        @ob_end_flush();
    }
    @flush();
}
