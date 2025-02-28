<?php

namespace Alezhu\LaravelNotisend;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider;

const NOTISEND = 'notisend';
const NOTISEND_CONFIG_FILE_NAME = NOTISEND . '.php';

class NotisendServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $publishablePath = $this->_getNotisendConfigPath();
            $this->publishes([$publishablePath => config_path(NOTISEND_CONFIG_FILE_NAME)], [NOTISEND . '-config','config']);
        }
        Mail::extend(NOTISEND, function ($config) {
            $httpClient = Http::getFacadeRoot();
            return new NotisendTransport($httpClient, $config);
        });
    }

    public function register()
    {
        parent::register();
        $publishablePath = $this->_getNotisendConfigPath();
        $this->mergeConfigFrom($publishablePath, NOTISEND);
        $config = $this->app['config'];
        $key = 'mail.mailers.' . NOTISEND;
        if(!$config->has($key)) {
            $notisend = $config[NOTISEND];
            $config->set($key, $notisend);
        }
        $key = 'mail.mailers.' . NOTISEND. '.transport';
        if(!$config->has($key)) {
            $config->set($key, NOTISEND);
        }
    }

    /**
     * @return string
     */
    protected function _getNotisendConfigPath(): string
    {
        return implode(DIRECTORY_SEPARATOR, [dirname(__DIR__) , 'config' , NOTISEND_CONFIG_FILE_NAME]);
    }

}