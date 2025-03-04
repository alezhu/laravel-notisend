<?php

use Alezhu\LaravelNotisend\NotisendConsts;

return [
    NotisendConsts::API_TOKEN => env(NotisendConsts::API_TOKEN_ENV),
    NotisendConsts::HOST => env(NotisendConsts::HOST_ENV, 'https://api.notisend.ru/v1'),
    NotisendConsts::PAYMENT => env(NotisendConsts::PAYMENT_ENV, 'credit_priority'),
];