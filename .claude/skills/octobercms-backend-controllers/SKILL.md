---
name: octobercms-backend-controllers
description: "Use when creating, modifying, or debugging October CMS backend controllers, including form controllers, list controllers, relation controllers, import/export controllers, or list structures. Activate when working with config_form.yaml, config_list.yaml, config_relation.yaml, fields.yaml, columns.yaml, scopes.yaml, controller views, toolbar partials, or any backend page behavior. Do not use for CMS theme pages or frontend components."
license: MIT
metadata:
  author: octobercms
---
# October CMS Backend Controllers

Backend controllers power the admin panel. They use **behaviors** - composable mixins configured via YAML files - to provide list, form, and relation management features.

## Scaffolding

```bash
php artisan create:controller Acme.Blog Posts
```

This creates:
- `controllers/Posts.php` - the controller class
- `controllers/posts/` - views directory with config files

## Controller Structure

```php
namespace Acme\Blog\Controllers;

class Posts extends \Backend\Classes\Controller
{
    public $implement = [
        \Backend\Behaviors\FormController::class,
        \Backend\Behaviors\ListController::class,
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';

    public $requiredPermissions = ['acme.blog.manage_posts'];

    public function __construct()
    {
        parent::__construct();

        \BackendMenu::setContext('Acme.Blog', 'blog', 'posts');
    }
}
```

## Available Behaviors

Behavior | Purpose | Config Property
--- | --- | ---
`FormController` | Create, update, preview forms | `$formConfig`
`ListController` | Sortable, searchable record lists | `$listConfig`
`RelationController` | Manage model relationships in forms | `$relationConfig`
`ImportExportController` | Import and export records | `$importExportConfig`

## List Configuration (config_list.yaml)

```yaml
title: Blog Posts
list: $/acme/blog/models/post/columns.yaml
modelClass: Acme\Blog\Models\Post
recordUrl: acme/blog/posts/update/:id
recordsPerPage: 20
showCheckboxes: true
defaultSort:
    column: created_at
    direction: desc
toolbar:
    buttons: list_toolbar
    search:
        prompt: Search posts...
filter: $/acme/blog/models/post/scopes.yaml
```

Additional list config properties: `recordOnClick`, `noRecordsMessage`, `showSetup`, `showSorting`, `showPageNumbers`, `perPageOptions`, `customViewPath`.

### List Columns (columns.yaml)

```yaml
columns:
    title:
        label: Title
        type: text
        searchable: true
        sortable: true
    category:
        label: Category
        relation: category
        select: name
        sortable: true
    is_published:
        label: Published
        type: switch
    created_at:
        label: Created
        type: datetime
```

Common column types: `text`, `number`, `switch`, `datetime`, `date`, `time`, `timesince`, `timetense`, `image`, `file`, `summary`, `partial`, `colorpicker`, `selectable`, `linkage`.

### List Filters (scopes.yaml)

```yaml
scopes:
    is_published:
        label: Published
        type: switch
        conditions:
            - is_published <> true
            - is_published = true
    category:
        label: Category
        type: group
        modelClass: Acme\Blog\Models\Category
        nameFrom: name
    created_at:
        label: Created
        type: date
        conditions:
            between: created_at >= :after AND created_at <= :before
    status:
        label: Status
        type: group
        options:
            draft: Draft
            published: Published
            archived: Archived
        conditions: status = :filtered
```

Available filter scope types: `group`, `switch`, `date`, `text`, `number`, `checkbox`, `dropdown`.

### Toolbar Partial (_list_toolbar.php)

```php
<div data-control="toolbar">
    <a href="<?= Backend::url('acme/blog/posts/create') ?>"
        class="btn btn-primary oc-icon-plus">
        New Post
    </a>
    <button
        type="button"
        class="btn btn-danger oc-icon-trash"
        data-request="onDelete"
        data-list-checked-trigger
        data-list-checked-request
        data-request-confirm="Delete selected posts?">
        Delete Selected
    </button>
</div>
```

## Form Configuration (config_form.yaml)

```yaml
name: Blog Post
form: $/acme/blog/models/post/fields.yaml
modelClass: Acme\Blog\Models\Post
defaultRedirect: acme/blog/posts
create:
    title: New Blog Post
    redirect: acme/blog/posts/update/:id
    redirectClose: acme/blog/posts
update:
    title: Edit Blog Post
    redirect: acme/blog/posts
    redirectClose: acme/blog/posts
preview:
    title: View Blog Post
```

### Form Designs

The `design` property controls form layout mode:

```yaml
design: sidebar
```

Available display modes: `custom` (default), `basic`, `survey`, `sidebar`, `popup`.

When using `popup`, the list controller can open forms in a popup via `recordOnClick: popup`.

### Form Fields (fields.yaml)

```yaml
fields:
    title:
        label: Title
        type: text
        span: full
    slug:
        label: Slug
        type: text
        span: full
        preset:
            field: title
            type: slug

tabs:
    fields:
        content:
            label: Content
            type: richeditor
            size: huge
            tab: Content
        featured_image:
            label: Featured Image
            type: fileupload
            mode: image
            tab: Media
        category:
            label: Category
            type: relation
            tab: Categories

secondaryTabs:
    fields:
        is_published:
            label: Published
            type: switch
            tab: Settings
        published_at:
            label: Publish Date
            type: datepicker
            tab: Settings
```

### Common Field Types

Type | Description
--- | ---
`text` | Single-line text
`email` | Email input
`password` | Password input
`textarea` | Multi-line text
`number` | Number input
`dropdown` | Dropdown select (uses `getFieldNameOptions()` on model)
`radio` | Radio buttons
`checkbox` | Single checkbox
`checkboxlist` | Multiple checkboxes
`balloon-selector` | Visual pill/balloon selector
`switch` | Toggle switch
`datepicker` | Date/time picker
`colorpicker` | Color picker
`richeditor` | Rich text HTML editor
`markdown` | Markdown editor
`codeeditor` | Code editor
`fileupload` | File upload
`mediafinder` | Media library file picker
`relation` | Relation dropdown/list
`recordfinder` | Related record finder with popup
`repeater` | Repeatable field groups
`nestedform` | Nested form for related/jsonable data
`taglist` | Tag list input
`datatable` | Editable table/grid
`sensitive` | Revealable password field (for API keys)
`pagefinder` | CMS page link selector
`partial` | Render a custom partial
`section` | Visual section divider
`hint` | Help text block

### Field Properties

Property | Description
--- | ---
`label` | Field label
`type` | Field widget type
`span` | `auto`, `full`, `left`, `right`, `row`
`tab` | Tab name for tabbed forms
`comment` | Help text below the field
`commentAbove` | Help text above the field
`placeholder` | Placeholder text
`default` | Default value
`required` | Show required indicator (visual only, use model `$rules` for validation)
`disabled` | Disable the field
`hidden` | Hide the field
`cssClass` | Custom CSS class
`readOnly` | Read-only field
`context` | Show only in specific contexts: `create`, `update`, `preview`
`dependsOn` | Other fields this field depends on for dynamic updates
`changeHandler` | AJAX handler to call when field value changes
`trigger` | Show/hide based on another field's value
`preset` | Auto-populate from another field

## Relation Configuration (config_relation.yaml)

### Has Many

```yaml
comments:
    label: Comments
    view:
        list: $/acme/blog/models/comment/columns.yaml
        toolbarButtons: create|delete
    manage:
        form: $/acme/blog/models/comment/fields.yaml
```

### Belongs To Many

```yaml
tags:
    label: Tags
    view:
        list: $/acme/blog/models/tag/columns.yaml
        toolbarButtons: add|remove
    manage:
        list: $/acme/blog/models/tag/columns.yaml
        form: $/acme/blog/models/tag/fields.yaml
```

### Belongs To Many with Pivot Data

```yaml
roles:
    label: Roles
    view:
        list: $/acme/blog/models/role/columns.yaml
        toolbarButtons: add|remove
    manage:
        list: $/acme/blog/models/role/columns.yaml
    pivot:
        form: $/acme/blog/models/role/pivot_fields.yaml
```

### Belongs To

```yaml
author:
    label: Author
    view:
        toolbarButtons: link|unlink
    manage:
        list: $/acme/blog/models/author/columns.yaml
```

### Has One

```yaml
profile:
    label: Profile
    view:
        toolbarButtons: update|delete
    manage:
        form: $/acme/blog/models/profile/fields.yaml
```

Additional relation config properties: `popupSize` (`giant`, `huge`, `large`, `small`, `tiny`, `adaptive`), `deferredBinding`, `customMessages`, `showFlash`, `showSearch`, `defaultSort`, `filter`.

## Controller Views

Views are PHP files in the controller's views directory:

```php
<!-- controllers/posts/index.php -->
<?php Block::put('breadcrumb') ?>
    <li><span>Blog</span></li>
    <li class="active"><span>Posts</span></li>
<?php Block::endPut() ?>

<?= $this->listRender() ?>
```

```php
<!-- controllers/posts/create.php -->
<?php Block::put('breadcrumb') ?>
    <li><a href="<?= Backend::url('acme/blog/posts') ?>">Posts</a></li>
    <li class="active"><span>New Post</span></li>
<?php Block::endPut() ?>

<?= $this->formRender() ?>
```

```php
<!-- controllers/posts/update.php -->
<?php Block::put('breadcrumb') ?>
    <li><a href="<?= Backend::url('acme/blog/posts') ?>">Posts</a></li>
    <li class="active"><span>Edit Post</span></li>
<?php Block::endPut() ?>

<?= $this->formRender() ?>
```

## Extending Controllers

### Adding Fields Dynamically

```php
Posts::extendFormFields(function ($form, $model, $context) {
    if (!$model instanceof \Acme\Blog\Models\Post) {
        return;
    }

    if ($form->isNested) {
        return;
    }

    $form->addTabFields([
        'custom_field' => [
            'label' => 'Custom Field',
            'type' => 'text',
            'tab' => 'Custom',
        ],
    ]);
});
```

### Adding Columns Dynamically

```php
Posts::extendListColumns(function ($list, $model) {
    if (!$model instanceof \Acme\Blog\Models\Post) {
        return;
    }

    $list->addColumns([
        'custom_column' => [
            'label' => 'Custom',
            'type' => 'text',
        ],
    ]);
});
```

## Controller Behavior Overrides

Controllers can override behavior methods to customize form and list behavior:

### Form Overrides

```php
class Posts extends \Backend\Classes\Controller
{
    // Before/after form save
    public function formBeforeSave($model) { }
    public function formAfterSave($model) { }

    // Before/after create specifically
    public function formBeforeCreate($model) { }
    public function formAfterCreate($model) { }

    // Before/after update specifically
    public function formBeforeUpdate($model) { }
    public function formAfterUpdate($model) { }

    // Before/after delete
    public function formBeforeDelete($model) { }
    public function formAfterDelete($model) { }

    // Extend the form query
    public function formExtendQuery($query) { }

    // Extend form fields programmatically
    public function formExtendFields($form) { }

    // Extend field configuration before rendering
    public function formExtendFieldsBefore($form) { }

    // Modify the form model
    public function formExtendModel($model) { }

    // Override redirect URL
    public function formGetRedirectUrl($context, $model) { }
}
```

### List Overrides

```php
class Posts extends \Backend\Classes\Controller
{
    // Extend the list query
    public function listExtendQuery($query, $definition = null) {
        $query->where('is_archived', false);
    }

    // Extend list columns programmatically
    public function listExtendColumns($list) { }

    // Modify records after retrieval
    public function listExtendRecords($records) { }

    // Inject custom CSS class on rows
    public function listInjectRowClass($record, $definition = null) {
        if ($record->is_archived) {
            return 'strike';
        }
    }

    // Override record click URL
    public function listOverrideRecordUrl($record, $definition = null) { }

    // Modify list filter scopes
    public function listFilterExtendScopes($filter) { }

    // Override how list filter queries work
    public function listFilterExtendQuery($query, $scope) { }
}
```

## Import/Export Controller

### Configuration (config_import_export.yaml)

```yaml
import:
    title: Import Subscribers
    modelClass: Acme\Campaign\Models\SubscriberImport
    list: $/acme/campaign/models/subscriber/columns.yaml

export:
    title: Export Subscribers
    modelClass: Acme\Campaign\Models\SubscriberExport
    list: $/acme/campaign/models/subscriber/columns.yaml
```

The default import/export format is JSON. CSV is also supported via `defaultFormatOptions`.

### Import Model

```php
class SubscriberImport extends \Backend\Models\ImportModel
{
    public $rules = [];

    public function importData($results, $sessionKey = null)
    {
        foreach ($results as $row => $data) {
            try {
                $subscriber = new Subscriber;
                $subscriber->fill($data);
                $subscriber->save();
                $this->logCreated();
            }
            catch (\Exception $ex) {
                $this->logError($row, $ex->getMessage());
            }
        }
    }
}
```

Log methods: `logCreated()`, `logUpdated()`, `logError($row, $message)`, `logWarning($row, $message)`, `logSkipped($row, $message)`.

### Export Model

```php
class SubscriberExport extends \Backend\Models\ExportModel
{
    public function exportData($columns, $sessionKey = null)
    {
        $records = Subscriber::all();
        $records->each(function ($record) use ($columns) {
            $record->addVisible($columns);
        });
        return $records->toArray();
    }
}
```

The `useList` export property integrates with the ListController, avoiding the need for a separate export model.

### Controller Views

Import and export use separate view files:

```php
<!-- controllers/subscribers/import.php -->
<?php Block::put('breadcrumb') ?>
    <li><span>Import Subscribers</span></li>
<?php Block::endPut() ?>

<?= $this->importRender() ?>
```

```php
<!-- controllers/subscribers/export.php -->
<?php Block::put('breadcrumb') ?>
    <li><span>Export Subscribers</span></li>
<?php Block::endPut() ?>

<?= $this->exportRender() ?>
```

## List Structure (Sorting & Reordering)

The `ListController` supports a **structure** property in `config_list.yaml` to enable drag-and-drop sorting and tree reordering. This replaces the deprecated `ReorderController`.

### Configuration (config_list.yaml)

```yaml
# ... existing list config ...

structure:
    showTree: true
    showReorder: true
    showSorting: false
    maxDepth: 2
```

Property | Description
--- | ---
**showTree** | Displays a tree hierarchy for parent/child records. Default: `true`
**treeExpanded** | If tree nodes should be expanded by default. Default: `true`
**showReorder** | Displays an interface for reordering records. Default: `true`
**showSorting** | Allows sorting records, disables the structure when sorted. Default: `true`
**maxDepth** | Defines the maximum levels allowed for reordering. Default: `null`
**dragRow** | Allow dragging the entire row in addition to the reorder handle. Default: `true`
**permissions** | Permissions the current backend user must have to modify the structure.

### Supported Model Traits

The model must use one of these traits depending on requirements:

- `NestedTree` - for fixed parent-child structures with specific ordering
- `SimpleTree` - for basic parent-child relationships
- `Sortable` - for flat list ordering without tree hierarchy

```php
class Category extends Model
{
    use \October\Rain\Database\Traits\NestedTree;
}
```

### Sorting Related Records

The `RelationController` also supports the **structure** property. For `belongsToMany` relations, use the `SortableRelation` trait with `pivotSortable`:

```php
class User extends Model
{
    use \October\Rain\Database\Traits\SortableRelation;

    public $belongsToMany = [
        'roles' => [
            Role::class,
            'table' => 'users_roles',
            'pivotSortable' => 'sort_order',
        ]
    ];
}
```

```yaml
# config_relation.yaml
roles:
    # ...
    structure:
        showReorder: true
        showTree: false
```

## Custom AJAX Handlers in Controllers

Controllers can define AJAX handlers called from backend pages:

```php
class Posts extends \Backend\Classes\Controller
{
    // ...

    public function onPublishSelected()
    {
        $checkedIds = post('checked', []);

        \Acme\Blog\Models\Post::whereIn('id', $checkedIds)
            ->update(['is_published' => true]);

        \Flash::success('Posts published successfully.');

        return $this->listRefresh();
    }
}
```

## Common Pitfalls

- Always set `BackendMenu::setContext()` in the constructor to highlight the correct menu.
- Config YAML paths use `$/` or `~/` prefix for absolute plugin paths.
- The `relation` field type works as a standalone dropdown/list. `RelationController` behavior is optional and auto-detected via `useController` (default `true`).
- Use `$requiredPermissions` to restrict controller access - this is an array of permission codes.
- Form views (`create.php`, `update.php`) must exist even if they just call `$this->formRender()`.
- The list toolbar partial name should match what's specified in `config_list.yaml` under `toolbar.buttons`.
- Use `$this->listRefresh()` and `$this->formRefreshFields('field')` to update the UI from AJAX handlers.
- Import/Export models extend `Backend\Models\ImportModel` and `Backend\Models\ExportModel` - not the regular plugin model.
- Controller behavior overrides (e.g., `formBeforeSave`) are methods on the controller, not the model.
- Multiple list definitions use array syntax: `$listConfig = ['posts' => 'config_list.yaml', 'comments' => 'config_comments_list.yaml']`.
- Always check `$form->isNested` in `extendFormFields` to avoid affecting repeaters and nested forms.
