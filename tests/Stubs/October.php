<?php

declare(strict_types=1);

/*
 * The October CMS modules (system, backend, dashboard) are licensed and come
 * from October's own gateway, so this file stands in for the few classes the
 * plugin touches. Each one keeps the signature and the behaviour of October
 * 4.4 that the plugin relies on; october/rain itself is the real package.
 */

namespace System\Classes {
    use Illuminate\Support\ServiceProvider;
    use October\Rain\Support\Traits\Singleton;

    abstract class PluginBase extends ServiceProvider
    {
        /** @var array<int, string> */
        public $require = [];

        /** @return array<string, mixed> */
        public function pluginDetails()
        {
            return [];
        }

        public function register()
        {
        }

        public function boot()
        {
        }

        /** @return array<string, mixed> */
        public function registerSettings()
        {
            return [];
        }

        /** @return array<string, mixed> */
        public function registerReportWidgets()
        {
            return [];
        }

        /**
         * @param string $key
         * @param class-string $class
         *
         * @return void
         */
        public function registerConsoleCommand($key, $class)
        {
            $key = 'command.' . $key;

            $this->app->singleton($key, $class);

            $this->commands($key);
        }
    }

    class PluginManager
    {
        use Singleton;

        /** @var array<string, bool> lower-case plugin code => whether it is disabled */
        public array $plugins = [];

        /**
         * @param string $namespace
         */
        public function hasPlugin($namespace): bool
        {
            return array_key_exists(strtolower($namespace), $this->plugins);
        }

        /**
         * @param string $id
         */
        public function isDisabled($id): bool
        {
            return $this->plugins[strtolower($id)] ?? true;
        }
    }

    class UpdateManager
    {
        use Singleton;

        public int $pending = 0;

        public function check(bool $force = false): int
        {
            return $this->pending;
        }
    }

    class SettingsManager
    {
        public const CATEGORY_SYSTEM = 'system::lang.system.categories.system';
    }
}

namespace System\Models {
    use October\Rain\Database\Model;

    class SettingModel extends Model
    {
        /** @var array<string, array<string, mixed>> what each settings model saved, by class */
        public static array $records = [];

        /** @var array<string, static> */
        protected static $instances = [];

        protected $table = 'system_settings';

        public $timestamps = false;

        /**
         * @param array<string, mixed> $attributes
         */
        public function __construct(array $attributes = [])
        {
            parent::__construct($attributes);
        }

        /**
         * @return static
         */
        public static function instance()
        {
            if (isset(static::$instances[static::class])) {
                return static::$instances[static::class];
            }

            $model = new static();

            if (array_key_exists(static::class, static::$records)) {
                $model->forceFill(static::$records[static::class]);
                $model->syncOriginal();
            } else {
                $model->initSettingsData();
                $model->fireEvent('model.initSettingsData');
            }

            return static::$instances[static::class] = $model;
        }

        /**
         * @param array<string, mixed>|string $key
         * @param mixed $value
         *
         * @return bool
         */
        public static function set($key, $value = null)
        {
            $data = is_array($key) ? $key : [$key => $value];

            $obj = static::instance();

            $obj->forceFill($data);

            return $obj->save();
        }

        /**
         * @param string $key
         * @param mixed $default
         *
         * @return mixed
         */
        public static function get($key, $default = null)
        {
            return data_get(static::instance()->getAttributes(), $key, $default);
        }

        /**
         * @return void
         */
        public function initSettingsData()
        {
        }

        /**
         * @param array<string, mixed>|null $options
         * @param string|null $sessionKey
         *
         * @return bool
         */
        public function save(?array $options = [], $sessionKey = null)
        {
            if ($this->fireModelEvent('saving') === false) {
                return false;
            }

            static::$records[static::class] = $this->getAttributes();

            $this->fireModelEvent('saved', false);

            return true;
        }

        /**
         * @return bool|null
         */
        public function delete()
        {
            $this->fireModelEvent('deleting');

            unset(static::$records[static::class], static::$instances[static::class]);

            $this->fireModelEvent('deleted', false);

            return true;
        }

        /**
         * @return void
         */
        public static function clearInternalCache()
        {
            static::$instances = [];
        }

        public static function resetStore(): void
        {
            static::$records = [];
            static::$instances = [];
        }
    }
}

namespace System\Controllers {
    class Settings
    {
    }
}

namespace Backend\Models {
    use October\Rain\Database\Model;

    /**
     * @property int $id
     * @property string $login
     * @property bool $is_superuser
     * @property \Illuminate\Support\Carbon|null $last_login
     */
    class User extends Model
    {
        protected $table = 'backend_users';

        public $timestamps = false;

        /** @var array<int, string> */
        protected $guarded = [];

        /** @var array<string, string> */
        protected $casts = [
            'is_superuser' => 'boolean',
            'last_login' => 'datetime',
        ];
    }
}

namespace Backend\Helpers {
    class Backend
    {
        /**
         * @param string|null $path
         * @param array<string, mixed>|null $parameters
         * @param bool|null $secure
         */
        public function url($path = null, $parameters = [], $secure = null): string
        {
            return url('admin/' . ltrim((string)$path, '/'));
        }
    }
}

namespace Backend\Classes {
    class AuthManager
    {
        /** @var array<int, string> the permissions the signed-in administrator holds */
        public array $permissions = [];

        public bool $isSuperuser = false;

        /**
         * @param array<int, string>|string $permissions
         * @param bool $all
         */
        public function userHasAccess($permissions, $all = true): bool
        {
            if ($this->isSuperuser) {
                return true;
            }

            $held = array_intersect((array)$permissions, $this->permissions);

            return $all ? count($held) === count((array)$permissions) : $held !== [];
        }
    }
}

namespace Backend\Facades {
    use October\Rain\Support\Facade;

    /**
     * @method static bool userHasAccess(string|array<int, string> $permissions, bool $all = true)
     */
    class BackendAuth extends Facade
    {
        protected static function getFacadeAccessor(): string
        {
            return 'backend.auth';
        }
    }

    /**
     * @method static string url(string|null $path = null, array<string, mixed>|null $parameters = [], bool|null $secure = null)
     */
    class Backend extends Facade
    {
        protected static function getFacadeAccessor(): string
        {
            return 'backend.helper';
        }
    }
}

namespace Backend\Classes {
    use October\Rain\Extension\Extendable;

    abstract class WidgetBase extends Extendable
    {
        /** @var string */
        public $alias;

        /** @var string */
        protected $defaultAlias = 'widget';

        /** @var object|null */
        protected $controller;

        /** @var string */
        protected $viewPath;

        /**
         * @param object|null $controller
         * @param array<string, mixed> $config
         */
        public function __construct($controller = null, $config = [])
        {
            $this->controller = $controller;
            $this->alias = $this->defaultAlias;

            $reflection = new \ReflectionClass($this);
            $this->viewPath = dirname((string)$reflection->getFileName())
                . '/' . strtolower($reflection->getShortName()) . '/partials';

            parent::__construct();
        }

        /**
         * @param string $partial
         * @param array<string, mixed> $params
         * @param bool $throwException
         *
         * @return mixed
         */
        public function makePartial($partial, $params = [], $throwException = true)
        {
            $path = sprintf('%s/%s_%s.htm', $this->viewPath, str_contains($partial, '/') ? dirname($partial) . '/' : '', basename($partial));

            if (!is_file($path)) {
                throw new \SystemException(sprintf('The partial "%s" is not found.', $partial));
            }

            return (function (string $__path, array $__params): string {
                extract($__params, EXTR_SKIP);
                ob_start();
                include $__path;

                return (string)ob_get_clean();
            })->call($this, $path, $params);
        }

        /**
         * @return object|null
         */
        public function getController()
        {
            return $this->controller;
        }
    }

    abstract class ReportWidgetBase extends WidgetBase
    {
        /** @var array<string, mixed> */
        protected $properties = [];

        /**
         * @param object|null $controller
         * @param object|null $dashReport
         * @param array<string, mixed> $properties
         */
        public function __construct($controller = null, $dashReport = null, $properties = [])
        {
            $defaults = [];

            foreach ($this->defineProperties() as $name => $definition) {
                $defaults[$name] = $definition['default'] ?? null;
            }

            $this->properties = array_merge($defaults, $properties);

            parent::__construct($controller);
        }

        /**
         * @return array<string, mixed>
         */
        public function defineProperties()
        {
            return [];
        }

        /**
         * @param string $name
         * @param mixed $default
         *
         * @return mixed
         */
        public function property($name, $default = null)
        {
            return $this->properties[$name] ?? $default;
        }

        /**
         * @return mixed
         */
        public function render()
        {
            return '';
        }
    }
}

namespace Backend\Widgets {
    use October\Rain\Database\Model;

    class Form
    {
        /** @var Model */
        public $model;

        public bool $isNested = false;

        /** @var array<string, array<string, mixed>> */
        public array $tabFields = [];

        public function __construct(private readonly ?object $controller, Model $model)
        {
            $this->model = $model;
        }

        /**
         * @return object|null
         */
        public function getController()
        {
            return $this->controller;
        }

        /**
         * @param array<string, array<string, mixed>> $fields
         *
         * @return static
         */
        public function addTabFields(array $fields)
        {
            $this->tabFields = array_merge($this->tabFields, $fields);

            return $this;
        }

        /**
         * @param string $name
         */
        public function removeField($name): bool
        {
            if (!isset($this->tabFields[$name])) {
                return false;
            }

            unset($this->tabFields[$name]);

            return true;
        }
    }
}

namespace {
    class_alias(October\Rain\Exception\SystemException::class, 'SystemException');
    class_alias(October\Rain\Exception\ApplicationException::class, 'ApplicationException');
}
