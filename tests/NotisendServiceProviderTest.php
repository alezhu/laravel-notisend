<?php


namespace {
    /** @noinspection PhpUnusedLocalVariableInspection */
    $MOCK_APP_PATH = implode(DIRECTORY_SEPARATOR, ['', 'home', 'projects', 'laravel-app']);
    if (!function_exists('config_path')) {
        /**
         * Get the configuration path.
         *
         * @param string $path
         * @return string
         */
        function config_path(string $path = ''): string
        {
            global $MOCK_APP_PATH;
            return implode(DIRECTORY_SEPARATOR, [$MOCK_APP_PATH, 'config', $path]);
        }
    }
}


namespace Alezhu\LaravelNotisend\Tests {

    use Alezhu\LaravelNotisend\NotisendServiceProvider;
    use Alezhu\LaravelNotisend\NotisendTransport;
    use ArrayAccess;
    use Closure;
    use Illuminate\Contracts\Config\Repository;
    use Illuminate\Contracts\Foundation\Application;
    use Illuminate\Contracts\Foundation\CachesConfiguration;
    use Illuminate\Http\Client\Factory;
    use Illuminate\Mail\Mailer;
    use Illuminate\Mail\MailManager;
    use Illuminate\Support\Facades\Facade;
    use Illuminate\Support\Facades\Http;
    use Illuminate\Support\Facades\Mail;
    use Mockery;


    class NotisendServiceProviderTest extends TestCase
    {

        private Application&Mockery\MockInterface $app;
        private NotisendServiceProvider $instance;

        public function test_boot_should_register_notisend_transport()
        {

            $mail = Mockery::mock(MailManager::class, [$this->app]);
            $mail->makePartial();
            Mail::swap($mail);

            $this->app->expects('runningInConsole')->andReturn(false);

            $config = Mockery::mock(Repository::class, ArrayAccess::class);

            $this->app->allows('offsetGet')
                ->with('config')
                ->andReturn($config);

            $config->expects('offsetGet')
                ->with('mail.driver')
                ->andReturnNull();

            $notisend = require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'config', 'notisend.php']);
            $notisend['transport'] = 'notisend';

            $config->expects('offsetGet')
                ->with('mail.mailers.notisend')
                ->andReturn($notisend);

            $this->app->expects('offsetGet')
                ->with('view')
                ->andReturn(Mockery::mock(\Illuminate\Contracts\View\Factory::class));

            $this->app->expects('offsetGet')
                ->with('events')
                ->andReturnNull();

            $http = Mockery::mock(Factory::class);
            Http::swap($http);

            $this->app->expects('bound')
                ->with('queue')
                ->andReturnNull();

            foreach (['from', 'reply_to', 'to', 'return_path'] as $type) {
                $config->expects('offsetGet')
                    ->with('mail.' . $type)
                    ->andReturnNull();
            }


            $this->instance->boot();

            $result = $mail->mailer('notisend');
            self::assertInstanceOf(Mailer::class, $result);
            self::assertInstanceOf(NotisendTransport::class, $result->getSymfonyTransport());

        }

        public function test_boot_should_register_publishers_when_run_in_console()
        {
            $mail = Mockery::mock(MailManager::class);
            Mail::swap($mail);
            $mail->expects('extend')
                ->with('notisend', Mockery::type(Closure::class))
                ->andReturns();

            $this->app->expects('runningInConsole')->andReturn(true);

            $this->instance->boot();

            self::assertArrayHasKey(NotisendServiceProvider::class, NotisendServiceProvider::$publishes);
            global $MOCK_APP_PATH;
            $configPath = implode(DIRECTORY_SEPARATOR, [$MOCK_APP_PATH, 'config', 'notisend.php']);
            $notisendConfigPath = implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'config', 'notisend.php']);
            self::assertArrayContains([$notisendConfigPath => $configPath], NotisendServiceProvider::$publishes[NotisendServiceProvider::class]);
            self::assertArrayHasKey('notisend-config', NotisendServiceProvider::$publishGroups);
            self::assertArrayContains([$notisendConfigPath => $configPath], NotisendServiceProvider::$publishGroups['notisend-config']);
            self::assertArrayHasKey('config', NotisendServiceProvider::$publishGroups);
            self::assertArrayContains([$notisendConfigPath => $configPath], NotisendServiceProvider::$publishGroups['config']);
        }

        public function test_register_should_add_notisend_to_mail_mailers_config()
        {
            $notisend = require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'config', 'notisend.php']);
            $this->app->expects('configurationIsCached')->andReturns(false);

            $config = Mockery::mock(Repository::class, ArrayAccess::class);
            $this->_prepareMergeConfig($config, $notisend);


            $config->expects('has')
                ->with('mail.mailers.notisend')
                ->andReturn(false);

            $config->expects('offsetGet')
                ->with('notisend')
                ->andReturn($notisend);
            $config->expects('set')
                ->with('mail.mailers.notisend', $notisend)
                ->andReturns();

            $config->expects('has')
                ->with('mail.mailers.notisend.transport')
                ->andReturn(true);

            $this->instance->register();
        }

        public function test_register_should_merge_configuration()
        {
            $notisend = require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'config', 'notisend.php']);
            $this->app->expects('configurationIsCached')->andReturns(false);

            $config = Mockery::mock(Repository::class, ArrayAccess::class);
            $this->_prepareMergeConfig($config, $notisend);


            $config->expects('has')
                ->with('mail.mailers.notisend')
                ->andReturn(true);

            $config->expects('has')
                ->with('mail.mailers.notisend.transport')
                ->andReturn(true);

            $this->instance->register();
        }

        public function test_register_should_set_transport_in_mail_mailers_notisend_config()
        {
            $notisend = require implode(DIRECTORY_SEPARATOR, [dirname(__DIR__), 'config', 'notisend.php']);
            $this->app->expects('configurationIsCached')->andReturns(false);

            $config = Mockery::mock(Repository::class, ArrayAccess::class);
            $this->_prepareMergeConfig($config, $notisend);


            $config->expects('has')
                ->with('mail.mailers.notisend')
                ->andReturn(true);

            $config->expects('has')
                ->with('mail.mailers.notisend.transport')
                ->andReturn(false);

            $config->expects('set')
                ->with('mail.mailers.notisend.transport', 'notisend')
                ->andReturns();

            $this->instance->register();
        }

        protected function _prepareMergeConfig(Repository|ArrayAccess|Mockery\MockInterface $config, array $notisend): void
        {
            $this->app->expects('make')
                ->with('config')
                ->andReturn($config);
            $config->expects('get')
                ->with('notisend', [])
                ->andReturn([]);
            $config->expects('set')
                ->with('notisend', $notisend)
                ->andReturns();

            $this->app->expects('offsetGet')
                ->with('config')
                ->andReturn($config);
        }

        protected function setUp(): void
        {
            parent::setUp();
            Facade::clearResolvedInstances();
            $this->app = Mockery::mock(Application::class, CachesConfiguration::class, ArrayAccess::class);
            $this->instance = new NotisendServiceProvider($this->app);
        }
    }
}