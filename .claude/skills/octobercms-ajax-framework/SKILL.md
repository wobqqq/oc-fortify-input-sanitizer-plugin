---
name: octobercms-ajax-framework
description: "Use when working with October CMS AJAX features, including data-request attributes, the jax JavaScript API, AJAX handlers, partial updates, form serialization, loading indicators, flash messages, validation, or the turbo router. Activate when the user mentions AJAX requests, data-request, onSomething handlers, partial rendering, or dynamic page updates without full reload. Do not use for standard REST API endpoints or non-AJAX server-side code."
license: MIT
metadata:
  author: octobercms
---
# October CMS AJAX Framework

October CMS includes a built-in AJAX framework (powered by Larajax) that allows dynamic page updates without full browser refreshes. It works in both CMS themes and the backend admin panel.

## Including the Framework

Add the framework to your layout:

```twig
{% framework %}          {# Core AJAX functionality #}
{% framework extras %}   {# Adds validation, loading indicators, flash messages, turbo router #}
```

## Data Attributes API

The simplest way to make AJAX requests - no JavaScript required:

### Basic Request

```html
<button data-request="onDoSomething">Click Me</button>
```

### Form Submission

```html
<form data-request="onSubmit">
    <input name="email" type="email" />
    <button type="submit">Subscribe</button>
</form>
```

Form data is automatically serialized and sent with the request.

### Updating Partials

```html
<form data-request="onCalculate" data-request-update="{ result: '#resultDiv' }">
    <input name="value1" /> + <input name="value2" />
    <button type="submit">Calculate</button>
</form>

<div id="resultDiv"></div>
```

The `data-request-update` attribute maps partial names to CSS selectors. After the handler runs, the named partial is rendered and injected into the target element.

### Available Data Attributes

Attribute | Description
--- | ---
`data-request` | Handler name (required), e.g., `onSubmit`
`data-request-update` | Partials to update: `{ partial: '#selector' }`
`data-request-confirm` | Confirmation message before sending
`data-request-redirect` | Redirect URL after success
`data-request-url` | Custom URL for the request (default: current page)
`data-request-data` | Extra data to send: `{ key: 'value' }`
`data-request-query` | Additional GET parameters for the URL query string
`data-request-form` | CSS selector of form to serialize (when outside a form)
`data-request-loading` | CSS selector of element to show during request
`data-request-message` | Progress message text to display during request
`data-request-progress-bar` | Enable the progress bar during request
`data-request-flash` | Enable flash messages from the server
`data-request-files` | Accept file uploads using FormData
`data-request-download` | Enable file download response
`data-request-bulk` | Send request as JSON for bulk data transactions
`data-request-before-update` | JavaScript to execute before partials are updated
`data-request-success` | JavaScript to execute on success
`data-request-error` | JavaScript to execute on error
`data-request-complete` | JavaScript to execute on completion (success or error)
`data-request-cancel` | JavaScript to execute if request is aborted/cancelled
`data-request-poll` | Auto-trigger AJAX at intervals (optional milliseconds value)
`data-track-input` | Auto-submit on input change (with optional debounce)
`data-browser-validate` | Enable browser-based client-side validation
`data-browser-target` | Window target for downloads (e.g., `_blank`)
`data-browser-redirect-back` | Use previous browser URL instead of redirect URL

### Selector Prefixes for Partial Updates

Use prefixes on CSS selectors in `data-request-update`:

```html
<!-- Replace element content (default) -->
data-request-update="{ 'list-item': '#itemList' }"

<!-- Append to the element -->
data-request-update="{ 'list-item': '@#itemList' }"

<!-- Prepend to the element -->
data-request-update="{ 'list-item': '^#itemList' }"

<!-- Replace the entire element (not just contents) -->
data-request-update="{ 'list-item': '!#itemList' }"

<!-- Use a custom CSS selector -->
data-request-update="{ 'list-item': '=[data-field-name=address]' }"
```

### Self-Updating Partials with ajaxPartial

Use the `{% ajaxPartial %}` tag to create self-updating partials:

```twig
{% ajaxPartial 'mytime' body %}
    The time is {{ 'now'|date('H:i:s') }}
    <button data-request="onRefreshTime" data-request-update="{ _self: true }">
        Refresh
    </button>
{% endpartial %}
```

Update using `{ _self: true }` or `{ mytime: true }` (the partial name).

### Global Partial Updates

Use a meta tag to merge an update definition with every AJAX request:

```html
<meta name="ajax-request-update" content="{ flash-messages: true }" />
```

## JavaScript API

For more control, use the `jax` JavaScript object:

### Basic Request

```js
jax.ajax('onDoSomething');
```

### Form Request

```js
jax.request('#myForm', 'onSubmit', {
    update: { result: '#resultDiv' },
    success: function(data, responseCode, xhr) {
        console.log('Done', data);
    },
    error: function(data, responseCode, xhr) {
        console.log('Error', data);
    }
});
```

### With Extra Data

```js
jax.ajax('onLoadItems', {
    data: { page: 2, category: 'news' },
    update: { 'items-list': '#itemContainer' }
});
```

### Options

Option | Description
--- | ---
`update` | Object mapping partials to selectors
`data` | Extra data to send
`query` | Object with additional GET query string parameters
`headers` | Object with custom request headers
`success` | Success callback `function(data, responseCode, xhr)`
`error` | Error callback `function(data, responseCode, xhr)`
`complete` | Completion callback (success or error)
`cancel` | Callback if user aborts/cancels
`beforeUpdate` | Callback before page elements are updated
`afterUpdate` | Callback after page elements are updated
`confirm` | Confirmation message string
`redirect` | Redirect URL on success
`loading` | Loading indicator selector
`message` | Progress message text
`progressBar` | Enable progress bar (boolean)
`form` | Form element to serialize
`flash` | Enable flash messages (boolean)
`files` | Accept file uploads (boolean)
`download` | Enable file download (boolean or filename)
`bulk` | Send as JSON for bulk transactions (boolean)
`browserValidate` | Enable browser validation (boolean)
`browserRedirectBack` | Use previous browser URL on redirect (boolean)

### Logic Handler Overrides

Override default behaviors globally on the `jax` object:

```js
jax.handleConfirmMessage = function(message, promise) { /* custom confirm */ };
jax.handleErrorMessage = function(message) { /* custom error display */ };
jax.handleValidationMessage = function(message, fields) { /* custom validation */ };
jax.handleFlashMessage = function(message, type) { /* custom flash */ };
jax.handleRedirectResponse = function(url) { /* custom redirect */ };
```

### Global AJAX Events

Events fired during the AJAX lifecycle:

Event | Fires On | Description
--- | --- | ---
`ajax:before-send` | window | Before request is sent
`ajax:before-update` | form | After response, before page update
`ajax:update` | updated element | After a single element is updated
`ajax:update-complete` | window | After all elements are updated
`ajax:request-success` | form | After successful request
`ajax:request-error` | form | If request encounters error
`ajax:error-message` | window | When error message is available
`ajax:confirm-message` | window | When confirmation is requested
`ajax:setup` | trigger element | Before request is formed
`ajax:promise` | trigger element | Directly before AJAX is sent
`ajax:fail` | trigger element | If AJAX fails
`ajax:done` | trigger element | If AJAX succeeds
`ajax:always` | trigger element | Regardless of success/failure

## Writing AJAX Handlers

### In CMS Pages/Layouts

Define handlers in the PHP code section:

```php
function onSubmitContactForm()
{
    $name = input('name');
    $email = input('email');

    // Process the form...

    $this['confirmation'] = "Thanks, {$name}!";
}
```

### In CMS Components

Define handlers as methods on the component class:

```php
class ContactForm extends ComponentBase
{
    public function onSubmit()
    {
        $data = post();

        // Validate and process...

        \Flash::success('Message sent!');
    }
}
```

Call component handlers with the component alias prefix:

```html
<button data-request="contactForm::onSubmit">Submit</button>
```

### In Backend Controllers

Define handlers as methods on the controller:

```php
class Posts extends \Backend\Classes\Controller
{
    public function onBulkPublish()
    {
        $ids = post('checked', []);
        Post::whereIn('id', $ids)->update(['is_published' => true]);
        \Flash::success('Posts published.');
        return $this->listRefresh();
    }
}
```

### Handler Priority

If handlers with the same name exist in multiple locations, the page handler takes priority over layout, and layout over component handlers.

### Pre-Handler Code

Use `onInit` in page/layout PHP or `init()` in components to run code before every AJAX handler:

```php
function onInit()
{
    // Runs before every AJAX handler on this page
}
```

## Handler Responses

### Returning Data

```php
function onGetStats()
{
    return [
        'totalUsers' => 500,
        'totalPosts' => 1200,
    ];
}
```

Access in JavaScript:
```html
<button data-request="onGetStats" data-request-success="alert(data.totalUsers)">
    Get Stats
</button>
```

### Partial Updates from PHP

In CMS pages/components, use `renderPartial`:

```php
function onUpdateList()
{
    $this['items'] = Item::all();

    return [
        '#itemList' => $this->renderPartial('item-list'),
    ];
}
```

In backend controllers, use `makePartial`:

```php
public function onUpdateList()
{
    return [
        '#itemList' => $this->makePartial('item-list'),
    ];
}
```

### Redirects

```php
function onSave()
{
    // Save logic...

    return \Redirect::to('/thank-you');
}
```

### Flash Messages

```php
function onSave()
{
    // Save logic...

    \Flash::success('Record saved successfully.');
    \Flash::error('Something went wrong.');
    \Flash::warning('Please check your input.');
}
```

Display flash messages in Twig:

```twig
{% flash %}
    <p class="flash-{{ type }}">
        {{ message }}
    </p>
{% endflash %}
```

### Dispatching Browser Events

```php
function onUpdateProfile()
{
    // Update logic...

    $this->dispatchBrowserEvent('profile:updated', ['name' => 'Jeff']);
}
```

Listen in JavaScript:

```js
addEventListener('profile:updated', function (event) {
    console.log('Updated:', event.detail.name);
});
```

Events are triggered after the request completes and before partials are updated. Call `event.preventDefault()` to prevent partial updates.

### Throwing AJAX Exceptions

```php
function onValidate()
{
    throw new \AjaxException([
        'error' => 'Validation failed',
        'fields' => ['email' => 'Email is required'],
    ]);
}
```

Note: When throwing `AjaxException`, partials will still be updated as normal.

## Form Validation

With `{% framework extras %}`, validation errors are automatically displayed:

```php
function onRegister()
{
    $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
    ];

    $validation = \Validator::make(input(), $rules);

    if ($validation->fails()) {
        throw new \ValidationException($validation);
    }

    // Process registration...
}
```

Validation errors automatically appear next to form fields when using `{% framework extras %}`.

## Loading Indicators

With `{% framework extras %}`, a loading indicator is shown automatically during AJAX requests. Customize with:

```html
<!-- Show a specific element during loading -->
<button data-request="onProcess" data-request-loading="#spinner">
    Process
</button>
<span id="spinner" style="display: none">Loading...</span>
```

## Turbo Router (PJAX)

With `{% framework extras %}`, page navigation can be AJAX-powered (turbo router is included). Enable it per-page with a meta tag:

```html
<meta name="turbo-visit-control" content="enable" />
```

### Programmatic Navigation

```js
jax.visit('/new-page');
jax.visit('/new-page', { action: 'replace' });  // Replace history, no back
jax.useTurbo();  // Check if turbo is enabled
```

### Disabling for Specific Links

```html
<a href="/external" data-turbo="false">Skip PJAX</a>
<div data-turbo="false">
    <a href="/link" data-turbo="true">Re-enable inside disabled container</a>
</div>
```

### Page Caching

```html
<meta name="turbo-cache-control" content="no-cache" />     <!-- Disable caching -->
<meta name="turbo-cache-control" content="no-preview" />   <!-- No preview on back/forward -->
```

### Preserving Elements Across Navigations

```html
<div id="player" data-turbo-permanent>
    <!-- This element persists across page navigations -->
</div>
```

### Script Loading

```html
<script data-turbo-eval="false">/* Only on first load */</script>
<script data-turbo-eval-once="analytics">/* Run once across all navigations */</script>
```

### Scroll Control

```html
<a href="/page" data-turbo-no-scroll>Preserve scroll position</a>
```

### Turbo Events

Event | Description
--- | ---
`render` | Page updated via PJAX or AJAX
`page:click` | Turbo link clicked
`page:before-visit` | Before visiting (except browser history)
`page:visit` | After clicked visit starts
`page:request-start` | Before page request
`page:request-end` | After page request
`page:before-cache` | Before page is cached
`page:before-render` | Before rendering (preventDefault to pause)
`page:render` | After rendered (fires twice: cache + network)
`page:load` | Once after initial + every visit
`page:loaded` | Like page:load but waits for scripts
`page:updated` | Like DOMContentLoaded for visits
`page:unload` | When page is disposed (clean up here)

### Ready Detection

```js
jax.pageReady().then(() => {
    // Page is ready
});

jax.waitFor(() => window.myLibrary).then(() => {
    // Library loaded
});
```

## File Uploads via AJAX

Upload files using `data-request-files` or the JavaScript API:

```html
<form data-request="onUpload" data-request-files>
    <input type="file" name="attachment" />
    <button type="submit">Upload</button>
</form>
```

Or with JavaScript:

```js
jax.request('#uploadForm', 'onUpload', {
    files: true
});
```

Handle the upload in PHP:

```php
function onUpload()
{
    $file = request()->file('attachment');

    $model = new \System\Models\File;
    $model->data = $file;
    $model->save();

    // Attach to a model
    $post = Post::find(input('post_id'));
    $post->featured_image()->add($model);
}
```

Note: The backend `fileupload` form widget handles file uploads automatically via deferred bindings - you don't need to write AJAX handlers for standard form file fields.

## AJAX Handlers in Partials

Partials have limited AJAX capability by default. Use the `{% ajaxPartial %}` tag to register handlers from within a partial:

```twig
{% ajaxPartial 'my-counter' body %}
    <span>Count: {{ count }}</span>
    <button data-request="onIncrement" data-request-update="{ _self: true }">+1</button>
{% endpartial %}
```

## Common Pitfalls

- Handler names must start with `on` (e.g., `onSubmit`, `onLoadMore`).
- The `data-request` attribute must be inside a `<form>` tag for form data to be serialized, or use `data-request-form` to specify a form.
- Use `input('key')` or `post('key')` in handlers to access request data, not `$_POST`.
- Component handlers need the alias prefix: `componentAlias::onHandler`.
- The `{% framework %}` tag must be present in the layout for AJAX to work.
- Partial names in `data-request-update` do not include the `.htm` extension.
- Partial names containing slashes or dashes must be quoted: `{ 'folder/my-partial': '#div' }`.
- Use `$this->renderPartial()` in CMS handlers and `$this->makePartial()` in backend controllers.
- The generic `onAjax` handler is available everywhere without defining it - useful for updating partials without server logic.
- Use `data-request-files` (not `enctype`) to enable file uploads via AJAX.
