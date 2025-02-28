<?php

use Alezhu\LaravelNotisend\NotisendConsts;

return [
    NotisendConsts::api_token->name => env(NotisendConsts::api_token->value),
    NotisendConsts::host->name => env(NotisendConsts::host->value, 'https://api.notisend.ru/v1'),
    NotisendConsts::payment->name => env(NotisendConsts::payment->value, 'credit_priority'),
];