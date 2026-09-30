---
name: octobercms-model-development
description: "Use when creating, modifying, or debugging October CMS models, including defining relationships, validation rules, model traits, file attachments, JSON attributes, model events, query scopes, or database structure. Activate when working with Eloquent models in October CMS, array-based relationship definitions, the Validation trait, SoftDelete, Sluggable, Sortable, or other October-specific model patterns. Do not use for Tailor EntryRecord models."
license: MIT
metadata:
  author: octobercms
---
# October CMS Model Development

October CMS models extend `October\Rain\Database\Model` (aliased as `Model`) and use array-based relationship definitions instead of Laravel's fluent method syntax.

## Scaffolding

```bash
php artisan create:model Acme.Blog Post
```

This creates:
- `models/Post.php` - model class
- `models/post/fields.yaml` - form field definitions
- `models/post/columns.yaml` - list column definitions
- `updates/create_posts_table.php` - migration file

## Model Structure

```php
namespace Acme\Blog\Models;

use Model;

class Post extends Model
{
    use \October\Rain\Database\Traits\Validation;
    use \October\Rain\Database\Traits\SoftDelete;
    use \October\Rain\Database\Traits\Sluggable;

    protected $table = 'acme_blog_posts';

    /**
     * @var array rules for validation.
     */
    public $rules = [
        'title' => 'required',
        'slug' => ['required', 'unique'],
        'email' => 'required|email',
    ];

    /**
     * @var array customMessages for validation.
     */
    public $customMessages = [
        'title.required' => 'Please enter a title.',
    ];

    /**
     * @var array slugs to generate.
     */
    protected $slugs = ['slug' => 'title'];

    /**
     * @var array jsonable for JSON-serialized columns.
     */
    protected $jsonable = ['metadata', 'options'];

    /**
     * @var array fillable fields for mass assignment.
     */
    protected $fillable = ['title', 'slug', 'content'];

    /**
     * @var array guarded fields from mass assignment.
     */
    protected $guarded = ['id'];

    /**
     * @var array hidden fields from serialization.
     */
    protected $hidden = ['password'];

    /**
     * @var array visible fields for serialization (whitelist alternative to $hidden).
     */
    protected $visible = [];

    /**
     * @var array appends accessor values to serialization.
     */
    protected $appends = ['full_name'];

    /**
     * @var array casts for scalar type casting.
     */
    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
        'options' => 'array',
    ];

    /*
     * Relationships
     */

    public $belongsTo = [
        'author' => [\Backend\Models\User::class],
        'category' => [\Acme\Blog\Models\Category::class],
    ];

    public $hasMany = [
        'comments' => [\Acme\Blog\Models\Comment::class, 'delete' => true],
    ];

    public $belongsToMany = [
        'tags' => [
            \Acme\Blog\Models\Tag::class,
            'table' => 'acme_blog_post_tags',
            'order' => 'name asc',
        ],
    ];

    public $attachOne = [
        'featured_image' => [\System\Models\File::class],
    ];

    public $attachMany = [
        'gallery' => [\System\Models\File::class],
    ];

    public $hasOne = [
        'meta' => [\Acme\Blog\Models\PostMeta::class],
    ];

    public $morphTo = [
        'commentable' => [],
    ];

    public $morphMany = [
        'activities' => [\Acme\Blog\Models\Activity::class, 'name' => 'subject'],
    ];
}
```

## Relationship Definitions

Relationships are defined as **public array properties** on the model. The key is the relation name, the value is either a class name or an array with the class name as the first element and additional parameters:

### Simple Definition

```php
public $hasMany = [
    'posts' => \Acme\Blog\Models\Post::class,
];
```

### Detailed Definition

```php
public $hasMany = [
    'posts' => [\Acme\Blog\Models\Post::class, 'delete' => true, 'order' => 'sort_order'],
];
```

### Relationship Types and Parameters

Type | Property | Key Parameters
--- | --- | ---
One to One | `$hasOne` | `key`, `otherKey`
One to Many | `$hasMany` | `key`, `otherKey`, `delete`, `order`, `conditions`
Belongs To | `$belongsTo` | `key`, `otherKey`, `default`
Many to Many | `$belongsToMany` | `table`, `key`, `otherKey`, `parentKey`, `relatedKey`, `pivot`, `pivotModel`, `pivotSortable`, `pivotKey`, `timestamps`, `detach`
Has Many Through | `$hasManyThrough` | `through`, `key`, `throughKey`, `secondOtherKey`
Has One Through | `$hasOneThrough` | `through`, `key`, `throughKey`, `secondOtherKey`
Morph One | `$morphOne` | `name`
Morph Many | `$morphMany` | `name`, `delete`, `order`
Morph To | `$morphTo` | (empty array `[]`)
Morph To Many | `$morphToMany` | `name`, `table`
Morphed By Many | `$morphedByMany` | `name`, `table`

### Common Relationship Parameters

Parameter | Description
--- | ---
`delete` | Delete related records when parent is deleted. Default: `false`
`softDelete` | Soft delete related records when parent is soft deleted. Default: `false`
`order` | Default ordering for the relation. E.g., `'name asc'`
`conditions` | Raw SQL conditions. E.g., `'is_active = 1'`
`scope` | Model scope method to apply
`push` | Whether the relation is saved via `push()`. Default: `true`
`replicate` | Whether the relation is duplicated when replicating. Default: `false`
`default` | For `belongsTo`, return an empty model instead of null (Null Object pattern)
`detach` | For `belongsToMany`, detach records when parent is deleted. Default: `true`
`relationClass` | Custom class for the relation object (available on all relations)

### File Attachments

```php
public $attachOne = [
    'avatar' => [\System\Models\File::class],
    'secret_file' => [\System\Models\File::class, 'public' => false],
];

public $attachMany = [
    'photos' => [\System\Models\File::class],
];
```

Access in Twig:
```twig
<img src="{{ post.featured_image.url }}" />

{% for photo in post.gallery %}
    <img src="{{ photo.thumbUrl(200, 200) }}" />
{% endfor %}
```

PHP methods: `getUrl()`, `getThumbUrl(width, height, options)`, `getLocalPath()`, `output()`, `download()`.

Creating attachments in PHP:
```php
$model->avatar = \System\Models\File::fromFile('/path/to/file.jpg');
$model->avatar = \System\Models\File::fromUrl('https://example.com/image.jpg');
$model->avatar = \System\Models\File::fromData('raw-data', 'filename.txt');
```

## Validation Trait

The `Validation` trait adds automatic validation on save:

```php
use \October\Rain\Database\Traits\Validation;

public $rules = [
    'name' => 'required|min:3',
    'email' => 'required|email|unique',
    'password' => 'required:create|min:8|confirmed',
    'links.*.url' => ['required', 'url'],
];

public $customMessages = [
    'name.required' => 'A name is required.',
];

public $attributeNames = [
    'email' => 'email address',
];
```

The `:create` and `:update` suffixes make rules context-specific:
- `'password' => 'required:create'` - only required when creating
- `'email' => 'unique:update'` - only unique check when updating

The `unique` rule is auto-configured and does not require a table name.

Use `$model->forceSave()` to save regardless of validation errors.

## Available Traits

Trait | Purpose
--- | ---
`Validation` | Automatic model validation with `$rules`
`SoftDelete` | Soft deletes (requires `deleted_at` column)
`Sluggable` | Auto-generate slugs from other fields
`SluggableTree` | Hierarchical slugs building full paths (requires `fullslug` column)
`Sortable` | Drag-and-drop sorting (requires `sort_order` column)
`SortableRelation` | Sort records in pivot/belongsToMany tables (uses `pivotSortable`)
`NestedTree` | Nested set tree structure (requires `parent_id`, `nest_left`, `nest_right`, `nest_depth`)
`SimpleTree` | Simple parent/child tree (requires `parent_id`)
`BaseIdentifier` | Random base64 encoded identifiers (requires `baseid` column)
`Defaultable` | Designate one record as default (requires `is_default` column)
`Purgeable` | Remove temporary attributes before save
`Revisionable` | Track changes to specified fields
`Nullable` | Set empty string attributes to null
`Hashable` | Hash specified attributes on save
`Encryptable` | Encrypt specified attributes
`UserFootprints` | Auto-populate `created_user_id` and `updated_user_id`
`Multisite` | Multi-site support with `site_id`

### Sluggable Example

```php
use \October\Rain\Database\Traits\Sluggable;

protected $slugs = ['slug' => 'title'];
```

### Sortable Example

```php
use \October\Rain\Database\Traits\Sortable;

// Requires sort_order integer column in the table
```

### NestedTree Example

```php
use \October\Rain\Database\Traits\NestedTree;

// Requires parent_id, nest_left, nest_right, nest_depth columns
```

### SortableRelation Example

```php
use \October\Rain\Database\Traits\SortableRelation;

public $belongsToMany = [
    'roles' => [Role::class, 'table' => 'user_roles', 'pivotSortable' => 'sort_order'],
];
```

## Model Events

Override these methods to hook into the model lifecycle:

```php
public function beforeCreate()
{
    // Before first save
    $this->code = strtoupper(Str::random(8));
}

public function afterCreate()
{
    // After first save
}

public function beforeSave()
{
    // Before every save (create and update)
}

public function afterSave()
{
    // After every save
}

public function beforeValidate()
{
    // Before validation runs
}

public function afterValidate()
{
    // After validation passes
}

public function beforeUpdate()
{
    // Before updating an existing record
}

public function afterUpdate()
{
    // After updating an existing record
    if ($this->title !== $this->original['title']) {
        // Title changed
    }
}

public function beforeDelete()
{
    // Before deletion
}

public function afterDelete()
{
    // After deletion
}

public function beforeRestore()
{
    // Before restoring a soft-deleted record
}

public function afterRestore()
{
    // After restoring a soft-deleted record
}

public function beforeFetch()
{
    // Before a model is populated from the database
}

public function afterFetch()
{
    // After a model is populated from the database
}
```

Returning `false` from a `before*` event cancels the operation.

## Query Scopes

```php
public function scopePublished($query)
{
    return $query->where('is_published', true);
}

public function scopeRecent($query)
{
    return $query->orderBy('created_at', 'desc');
}

public function scopeApplyCategory($query, $categoryId)
{
    return $query->where('category_id', $categoryId);
}
```

Usage:
```php
$posts = Post::published()->recent()->get();
$posts = Post::applyCategory(5)->get();
```

## Accessors and Mutators

Define accessors and mutators using `getFieldAttribute` / `setFieldAttribute`:

```php
// Accessor - transforms value when reading
public function getFullNameAttribute()
{
    return $this->first_name . ' ' . $this->last_name;
}

// Mutator - transforms value when writing
public function setPasswordAttribute($value)
{
    $this->attributes['password'] = bcrypt($value);
}
```

Access: `$model->full_name`, `$model->password = 'secret'`.

External accessors/mutators can be added via events:

```php
$model->bindEvent('model.getAttribute', function ($key, $value) {
    if ($key === 'full_address') {
        return $this->street . ', ' . $this->city;
    }
});
```

## Eager Loading

Use the `$with` property to always eager-load relations:

```php
protected $with = ['category', 'author'];
```

Or eager-load on demand:

```php
$posts = Post::with(['category', 'comments' => function ($query) {
    $query->where('is_approved', true);
}])->get();
```

## Deferred Bindings

Deferred bindings allow relations and file attachments to be linked to a model before it is saved. This is how October CMS forms handle relations and file uploads on create forms (where the model doesn't exist yet).

```php
// Generate a session key (forms provide this automatically)
$sessionKey = uniqid('session_key', true);

// Defer-add a relation
$post->comments()->add($comment, $sessionKey);

// Defer-remove a relation
$post->comments()->remove($comment, $sessionKey);

// Query with deferred records included
$post->comments()->withDeferred($sessionKey)->get();

// Commit deferred bindings when saving
$post->save(['sessionKey' => $sessionKey]);

// Commit deferred bindings without saving
$post->commitDeferred($sessionKey);

// Cancel all deferred bindings
$post->cancelDeferred($sessionKey);
```

File uploads through the backend form widget automatically use deferred bindings - the session key is managed by the form behavior.

## Extending Models

Extend models from other plugins in your `boot()` method:

```php
\Acme\User\Models\User::extend(function ($model) {
    // Add a relation
    $model->hasMany['posts'] = \Acme\Blog\Models\Post::class;

    // Add a dynamic method
    $model->addDynamicMethod('getLatestPost', function () use ($model) {
        return $model->posts()->orderBy('created_at', 'desc')->first();
    });

    // Add validation rules
    $model->rules['phone'] = 'nullable|string';

    // Add protected properties
    $model->addFillable(['phone', 'bio']);
    $model->addJsonable(['preferences']);
    $model->addCasts(['is_verified' => 'boolean']);

    // Listen to local events
    $model->bindEvent('model.beforeSave', function () use ($model) {
        // Modify model before save
    });
});
```

## Dropdown Options

Define options for form dropdowns on the model:

```php
public function getStatusOptions()
{
    return [
        'draft' => 'Draft',
        'published' => 'Published',
        'archived' => 'Archived',
    ];
}
```

This is automatically used by `type: dropdown` fields in `fields.yaml` when the field name is `status`.

## Common Pitfalls

- Use **array-based** relationship definitions (`$hasMany = [...]`), never Laravel's fluent methods (`$this->hasMany(...)`).
- Table names use the `{author}_{plugin}_{plural}` convention (e.g., `acme_blog_posts`).
- The `Validation` trait validates automatically on `save()` - you can also call `validate()` manually.
- Both `$jsonable` and `$casts = ['field' => 'array']` work for JSON columns. Use whichever matches existing code patterns.
- File attachments use `System\Models\File`, not any other file model.
- Model events are method overrides (`beforeSave()`), not event listeners or closures.
- The `$rules` property supports `:create` and `:update` context suffixes.
- Always import the `Model` alias (`use Model;`), not the full `October\Rain\Database\Model` class directly.
- Pivot table names for `belongsToMany` follow the convention `{author}_{plugin}_{model1}_{model2}` in alphabetical order.
- Deferred bindings are handled automatically by the form behavior - you rarely need to manage session keys manually.
- Use `$model->bindEvent()` for local events and `\Event::listen()` for global events - they serve different purposes.
- Use `.url` and `.thumbUrl()` in Twig for file attachments, not `.path` or `.thumb`.
