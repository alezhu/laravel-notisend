<?php

namespace Alezhu\LaravelNotisend;



enum NotisendConsts: string
{
    case api_token = 'NOTISEND_API_TOKEN';
    case host = 'NOTISEND_API_HOST';
    case payment = 'NOTISEND_PAYMENT';
}