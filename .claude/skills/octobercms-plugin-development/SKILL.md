---
name: octobercms-plugin-development
description: "Use when creating, modifying, or scaffolding October CMS plugins. Activate when working with Plugin.php registration files, plugin directory structure, version.yaml files, migrations, registering components, permissions, navigation, settings, form widgets, or any plugin lifecycle task. Also use when the user mentions creating a new plugin from scratch. Do not use for theme-only or frontend-only tasks."
license: MIT
metadata:
  author: octobercms
---
# October CMS Plugin Development

## Scaffolding

Use `php artisan create:plugin Acme.Blog` to scaffold a new plugin. This creates the directory structure and registration file.

All scaffolding commands follow the pattern `create:{type} Acme.Blog {Name}`:

Command | Creates
--- | ---
`create:plugin Acme.Blog` | Plugin with registration file
`create:model Acme.Blog Post` | Model with migration and YAML configs
`create:controller Acme.Blog Posts` | Backend controller with views
`create:component Acme.Blog BlogPost` | CMS component
`create:vuecomponent Acme.Blog PostEditor` | Vue 3 component (backend or CMS)
`create:command Acme.Blog MyCommand` | Console command
`create:migration Acme.Blog AddStatusColumn` | Migration file
`create:formwidget Acme.Blog MyWidget` | Custom form widget
`create:filterwidget Acme.Blog MyFilter` | Custom filter widget
`create:reportwidget Acme.Blog MyReport` | Dashboard report widget
`create:contentfield Acme.Blog MyField` | Tailor content field
`create:job Acme.Blog ProcessData` | Queue job class
`create:factory Acme.Blog PostFactory` | Model factory
`create:seeder Acme.Blog PostSeeder` | Database seeder
`create:test Acme.Blog PostTest` | Test class

## Plugin Registration File

Every plugin has a `Plugin.php` extending `System\Classes\PluginBase`:

```php
namespace Acme\Blog;

class Plugin extends \System\Classes\PluginBase
{
    /**
     * @var array require these plugins
     */
    public $require = ['October.Drivers'];

    public function pluginDetails()
    {
        return [
            'name' => 'Blog',
            'description' => 'Provides blog features.',
            'author' => 'Acme',
            'icon' => 'icon-pencil',
            'homepage' => 'https://example.com',
        ];
    }

    public function register()
    {
        // Called when plugin is first registered (DI, singletons, commands)
        $this->discoverConsoleCommands();
    }

    public function boot()
    {
        // Called on every request (event listeners, extending models)
    }

    public function registerComponents()
    {
        return [
            \Acme\Blog\Components\Post::class => 'blogPost',
        ];
    }

    public function registerPermissions()
    {
        return [
            'acme.blog.manage_posts' => [
                'label' => 'Manage Blog Posts',
                'tab' => 'Blog',
                'order' => 200,
                'roles' => ['developer'],
            ],
        ];
    }

    public function registerNavigation()
    {
        return [
            'blog' => [
                'label' => 'Blog',
                'url' => \Backend::url('acme/blog/posts'),
                'icon' => 'icon-pencil',
                'permissions' => ['acme.blog.*'],
                'order' => 500,
                'counter' => [\Acme\Blog\Models\Post::class, 'getUnpublishedCount'],
                'counterLabel' => 'Unpublished posts',
                'sideMenu' => [
                    'posts' => [
                        'label' => 'Posts',
                        'icon' => 'icon-file-text-o',
                        'url' => \Backend::url('acme/blog/posts'),
                        'permissions' => ['acme.blog.manage_posts'],
                    ],
                ],
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label' => 'Blog Settings',
                'description' => 'Manage blog settings.',
                'category' => 'Blog',
                'icon' => 'icon-cog',
                'class' => \Acme\Blog\Models\Settings::class,
                'order' => 500,
                'permissions' => ['acme.blog.manage_settings'],
                'keywords' => 'blog settings configuration',
                'size' => 'large',
            ],
        ];
    }

    public function registerFormWidgets()
    {
        return [
            \Acme\Blog\FormWidgets\MyWidget::class => 'mywidget',
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'acme.blog:post-notification' => 'acme.blog::mail.post-notification',
        ];
    }
}
```

### Registration Methods

Method | Purpose
--- | ---
`pluginDetails()` | Plugin metadata (name, description, author, icon, homepage)
`register()` | Service registration, singletons, commands (called first)
`boot()` | Event listeners, model extensions (called after all plugins registered)
`registerComponents()` | CMS frontend components
`registerPermissions()` | Backend permission definitions
`registerNavigation()` | Backend menu items and side menus
`registerSettings()` | Settings pages (model-based or URL-based)
`registerFormWidgets()` | Custom form field widgets
`registerFilterWidgets()` | Custom filter widgets
`registerReportWidgets()` | Backend dashboard report widgets
`registerMailTemplates()` | Mail template definitions
`registerMarkupTags()` | Custom Twig filters and functions
`registerListColumnTypes()` | Custom list column types
`registerContentFields()` | Tailor content fields
`registerSchedule($schedule)` | Task scheduler definitions

## Version History

The `updates/version.yaml` file tracks versions and references migration scripts:

```yaml
v1.0.1: First version
v1.0.2:
    - Set up database tables
    - 000001_create_posts.php
    - 000002_create_categories.php
v1.0.3:
    - Seed default categories
    - seed_tables.php
v1.1.0: Added comments support
v1.2.0:
    - Added tags
    - 000003_create_tags.php
    - 000004_create_post_tags.php
v2.0.0:
    - Major upgrade
    - migrate_v2_0_0.php
```

## Migrations

Migration files live in `updates/` and use zero-padded numbered filenames:

```php
<?php // updates/000001_create_posts.php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        Schema::create('acme_blog_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('slug')->index();
            $table->mediumText('content')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('acme_blog_posts');
    }
};
```

### Migration Conventions

- **Filenames** use zero-padded numbering: `000001_create_posts.php`, `000002_create_categories.php`
- **Anonymous classes**: prefer `return new class extends Migration` (named classes also supported)
- **IDs**: use `$table->increments('id')` not `$table->id()`
- **Table names**: `{author}_{plugin}_{plural_name}` in snake_case (e.g., `acme_blog_posts`)
- **Extension migrations** that modify another plugin's tables use `x` prefix: `x00001_extend_user_groups.php`
- **Version migrations** for major/minor upgrades use `migrate_v{X}_{Y}_{Z}.php` naming (see below)

### Version Migrations

For major or minor version bumps, use version migration scripts named `migrate_vX_Y_Z.php`. These are "patch" migrations that handle schema changes and data migration for upgrades. They can safely be deleted later once all installations have upgraded past that version.

Reference them in `version.yaml` under the version entry:

```yaml
v3.0.0:
    - Major Upgrade to Blog Plugin
    - migrate_v3_0_0.php
v3.1.0:
    - New tagging system
    - migrate_v3_1_0.php
```

Version migrations typically check if changes are needed before applying them, and can use `db.updater` to run numbered migrations that may have been missed:

```php
<?php // updates/migrate_v3_0_0.php

use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

return new class extends Migration
{
    public function up()
    {
        // Run a numbered migration if its table doesn't exist yet
        $updater = App::make('db.updater');
        if (!Schema::hasTable('acme_blog_tags')) {
            $updater->setUp(__DIR__.'/000003_create_tags.php');
        }

        // Add new columns if they don't exist
        if (!Schema::hasColumn('acme_blog_posts', 'summary')) {
            Schema::table('acme_blog_posts', function (Blueprint $table) {
                $table->text('summary')->nullable();
                $table->boolean('is_featured')->default(false);
            });
        }

        // Migrate data from old columns to new
        if (Schema::hasColumn('acme_blog_posts', 'old_status')) {
            Db::update("update acme_blog_posts set is_published=1 where old_status='active'");
        }
    }

    public function down()
    {
    }
};
```

Key patterns for version migrations:
- Always guard with `Schema::hasColumn()` / `Schema::hasTable()` checks - the migration must be safe to run on both fresh installs and upgrades
- Use `App::make('db.updater')->setUp()` to run numbered migrations that may not have run yet
- Use `Db::update()` for data migration between columns
- The `down()` method is typically empty - these are one-way upgrade patches

## Dependencies

Declare plugin dependencies with the `$require` property:

```php
public $require = ['Acme.User', 'October.Drivers'];
```

Dependencies are installed automatically and the plugin is disabled if dependencies are missing.

## Extending Other Plugins

Use the `boot()` method to extend models and controllers from other plugins:

```php
public function boot()
{
    // Add a relation to another plugin's model
    \Acme\User\Models\User::extend(function ($model) {
        $model->hasMany['posts'] = \Acme\Blog\Models\Post::class;
    });

    // Extend a backend controller
    \Acme\User\Controllers\Users::extendFormFields(function ($form, $model, $context) {
        if (!$model instanceof \Acme\User\Models\User) {
            return;
        }

        if ($form->isNested) {
            return;
        }

        $form->addTabFields([
            'posts' => [
                'label' => 'Blog Posts',
                'type' => 'partial',
                'path' => '$/acme/blog/controllers/posts/_user_posts.php',
                'tab' => 'Blog',
            ],
        ]);
    });
}
```

## Settings Model

Plugin settings use `SettingModel` instead of a custom database table:

```php
namespace Acme\Blog\Models;

class Settings extends \System\Models\SettingModel
{
    public $settingsCode = 'acme_blog_settings';
    public $settingsFields = 'fields.yaml';
}
```

The `fields.yaml` sits alongside the model in `models/settings/fields.yaml`.

Reading and writing settings:

```php
// Read
$value = Settings::get('api_key');
$value = Settings::get('api_key', 'default');

// Write
Settings::set('api_key', 'ABCD');
Settings::set(['api_key' => 'ABCD', 'enabled' => true]);

// Instance access
$settings = Settings::instance();
$settings->api_key = 'ABCD';
$settings->save();
```

Register in Plugin.php with `registerSettings()` (see above).

## Event Listeners

Register event listeners in the `boot()` method:

```php
public function boot()
{
    // Global events
    \Event::listen('backend.form.extendFields', function ($widget) {
        if (!$widget->getController() instanceof \Acme\User\Controllers\Users) {
            return;
        }

        if ($widget->isNested) {
            return;
        }

        $widget->addFields([
            'phone' => [
                'label' => 'Phone',
                'type' => 'text',
            ],
        ]);
    });

    \Event::listen('backend.list.extendColumns', function ($widget) {
        if (!$widget->getController() instanceof \Acme\User\Controllers\Users) {
            return;
        }

        $widget->addColumns([
            'phone' => ['label' => 'Phone'],
        ]);
    });

    // Local model events
    \Acme\User\Models\User::extend(function ($model) {
        $model->bindEvent('model.afterSave', function () use ($model) {
            // React to user save
        });
    });
}
```

Common events:
- `backend.form.extendFields` - add/remove form fields
- `backend.form.extendFieldsBefore` - modify field config before rendering
- `backend.list.extendColumns` - add/remove list columns
- `backend.filter.extendScopes` - add/remove filter scopes
- `system.extendConfigFile` - modify config values
- `cms.page.beforeDisplay` - before a CMS page renders
- Fire custom events: `Event::fire('acme.blog.afterPublish', [$post])`

## Console Commands

Create a command with `php artisan create:command Acme.Blog MyCommand`:

```php
namespace Acme\Blog\Console;

use Illuminate\Console\Command;

class MyCommand extends Command
{
    protected $signature = 'acme:sync-posts {--force}';

    protected $description = 'Synchronize blog posts.';

    public function handle()
    {
        if ($this->option('force')) {
            $this->info('Force syncing...');
        }

        $this->info('Done.');
    }
}
```

Register commands using auto-discovery (preferred):

```php
public function register()
{
    $this->discoverConsoleCommands();
}
```

This automatically finds and registers all command classes in the plugin's `console` directory.

Or register individually:

```php
public function register()
{
    $this->registerConsoleCommand('acme.syncposts', \Acme\Blog\Console\MyCommand::class);
}
```

## Localization

Language strings use JSON files in `lang/`:

```
plugins/acme/blog/lang/
├── en.json
└── fr.json
```

JSON format:

```json
{
    "Manage Posts": "Manage Posts",
    ":name created a post": ":name created a post"
}
```

Access strings:

```php
echo __('Manage Posts');
echo __(':name created a post', ['name' => 'Jeff']);
echo __('There is one post|There are many posts', 3);
```

## Mail Templates

Mail views use a three-section format (subject, plain text, HTML) in `views/mail/*.htm`:

```
subject = "New post published: {{ title }}"
==
A new post "{{ title }}" was published on {{ site_name }}.
==
<p>A new post <strong>{{ title }}</strong> was published on {{ site_name }}.</p>
```

Sending mail:

```php
$vars = ['title' => $post->title, 'site_name' => 'My Blog'];

\Mail::send('acme.blog::mail.post-notification', $vars, function ($message) {
    $message->to('admin@example.com');
});

// Quick send
\Mail::sendTo('admin@example.com', 'acme.blog::mail.post-notification', $vars);
```

Register templates in Plugin.php with `registerMailTemplates()` (see above).

## Common Pitfalls

- Always use `PluginBase` not Laravel's `ServiceProvider` for plugin registration.
- The `register()` method runs before all plugins are loaded - do not reference other plugins here; use `boot()` instead.
- Table names must be globally unique - always prefix with author and plugin name.
- Never modify migrations that have run in production - create new migration files instead.
- Migration filenames are zero-padded numbered (`000001_create_posts.php`), not descriptive names.
- The `$require` array uses dot notation (`Acme.Blog`), not namespace notation.
- Settings models do not need a migration - they store data automatically in the `system_settings` table.
- Always check the controller/model type in event listeners to avoid extending the wrong form or list.
- Always check `$widget->isNested` in form extend listeners to avoid affecting repeaters and nested forms.
- Use `discoverConsoleCommands()` in `register()` for automatic command discovery, or `registerConsoleCommand()` for manual registration.
