<?php

declare(strict_types=1);

namespace Wobqqq\FortifyInputSanitizer\Tests;

use Backend\Classes\AuthManager;
use Backend\Helpers\Backend;
use Backend\Models\User;
use Illuminate\Contracts\Config\Repository;
use October\Rain\Database\Model;
use October\Rain\Events\EventServiceProvider;
use October\Rain\Extension\Container as ExtensionContainer;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReflectionProperty;
use System\Classes\PluginManager;
use System\Models\SettingModel;
use Wobqqq\Fortify\Plugin as FortifyPlugin;
use Wobqqq\FortifyInputSanitizer\Instances\InputSanitizerDtoInstance;
use Wobqqq\FortifyInputSanitizer\Plugin;
use Wobqqq\FortifyInputSanitizer\Services\InputSanitizerService;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        SettingModel::resetStore();
        PluginManager::forgetInstance();

        self::bootPlugins();
    }

    /**
     * October keeps model event listeners and extend() callbacks in statics, while every
     * test boots a new application and the plugins again.
     */
    protected function tearDown(): void
    {
        ExtensionContainer::clearExtensions();
        User::flushEventListeners();
        SettingModel::flushEventListeners();
        Model::flushEventListeners();

        parent::tearDown();
    }

    /**
     * Boots the core and the module the way October does on every request.
     */
    public static function bootPlugins(): void
    {
        SettingModel::clearInternalCache();
        InputSanitizerDtoInstance::forgetInstance();
        (new ReflectionProperty(InputSanitizerService::class, 'addMiddleware'))->setValue(null, false);
        (new ReflectionProperty(\Wobqqq\Fortify\Services\ConfigService::class, 'overrideConfig'))->setValue(null, false);
        \Wobqqq\Fortify\Instances\ConfigDtoInstance::forgetInstance();

        $app = app();

        foreach ([new FortifyPlugin($app), new Plugin($app)] as $plugin) {
            $plugin->register();
            $plugin->boot();
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [EventServiceProvider::class];
    }

    /**
     * @return array<string, class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'Backend' => \Backend\Facades\Backend::class,
            'BackendAuth' => \Backend\Facades\BackendAuth::class,
            'Event' => \October\Rain\Support\Facades\Event::class,
            'Input' => \October\Rain\Support\Facades\Input::class,
            'Str' => \October\Rain\Support\Str::class,
            'Url' => \Illuminate\Support\Facades\URL::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $core = __DIR__ . '/../vendor/wobqqq/fortify-plugin';

        /** @var \Illuminate\Translation\Translator $translator */
        $translator = $app->make('translator');
        $translator->addNamespace('wobqqq.fortify', $core . '/lang');

        $app->singleton('backend.helper', Backend::class);
        $app->singleton('backend.auth', AuthManager::class);

        $config = $app->make(Repository::class);
        $config->set('cms.middleware_group', 'web');
        $config->set('backend.middleware_group', 'web');
    }
}
