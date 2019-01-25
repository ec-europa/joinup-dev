# Description

Shows a friendly page to the users when an exception or an error are thrown,
instead of the white page with the error backtrace. The module is able to attach
an Universally Unique Identifier (UUID) to each error/exception, so that the
user is able to refer the error to the site's contact support team. The UUID is
logged in the Drupal log and, optionally, injected in the log entry message.

# Technical aspects

## Drupal services, configuration, theming

Uncaught exceptions indicates that something really wrong has happened on a
site. There is a great chance that some, or all, of Drupal services are not
available. For this reason, in the module we are avoiding to use or refer Drupal
services as much as is possible. For the same reason, the module cannot be
configured using the configuration management offered by Drupal. Instead, the
module will use the settings stored in the `settings.php` file to configure its
behavior.

This is true also for theming. The Drupal/Twig rendering engine cannot be used,
as it would involve usage of Drupal services as well. However, the outputted
page or message can still be customized because they are simple static HTML
pages with some raw replacement tokens.

## How it works?

### Uncaught exceptions

The module swaps the core `ExceptionLoggingSubscriber`, which is responsible
for logging the uncaught exceptions to the Drupal logger, with its own
`ErrorPageExceptionLoggingSubscriber`. Actually the module class replaces the
`::onError()` method and, if case, logs also the UUID. Then, the module adds a
new subscriber, `ErrorPageFinalExceptionSubscriber`, which acts just before the
core `FinalExceptionSubscriber` by showing the output in its own way and
stopping the propagation of the `KernelEvents::EXCEPTION` event.

## Fatal and user errors

The module uses its own error/exception handlers, instead of
`_drupal_error_handler()` and `_drupal_exception_handler()`. Because those
procedural functions are hard to be extended or overwritten, the core code is
90% copied in module's handlers. These are slightly changed to allow using a
custom outputted HTML rendered markup.  

# Getting started

## Install

Install the module as any other module. There are no other dependencies.

## Configure

As explained earlier, the entire configuration is done via `settings/php`:

### ErrorPageErrorHandler autoload

As the container might not be available yet when an uncaught exception or a
fatal error occur, the auto-loading might not work for extensions such as
modules. Thus we cannot register our error/exception handlers, which live in
`ErrorPageErrorHandler`. The system must be instructed explicitly from where to
load the class. If you are using composer this is done automatically because in
the module's `composer.json` we've added the class as a `classmap` entry of
`autoload`. If, for some reasons, you're not using Composer, then you'll need to
explicitly require the file. In this case, in `settings.php` just add this line:

```php
// Note that the path to error handler might be different on some installations.
require_once 'modules/custom/error_page/src/ErrorPageErrorHandler.php';
```

### Set the custom error handlers

Next two lines should be added in `settings.php`:

```php
set_error_handler(['Drupal\error_page\ErrorPageErrorHandler', 'handleError']);
set_exception_handler([
  'Drupal\error_page\ErrorPageErrorHandler',
  'handleException',
]);
```

### Configuration

In `settings.php`:

```php
// Defaults to TRUE.
$settings['error_page']['uuid']['enabled'] = TRUE;
// Defaults to TRUE.
$settings['error_page']['uuid']['add_to_message'] = TRUE;
// Point to the path where the customizable HTML markup files are placed. It's
// recommended that the custom template location is placed outside the webtree
// or is protected from the public access with a file, such as markup/.htaccess.
$settings['error_page']['template_dir'] = DRUPAL_ROOT . '/../path/to/templates';
```

### All together

Your `settings.php` section might look like:

```php
// Only if you don't use composer.
require_once 'modules/custom/error_page/src/ErrorPageErrorHandler.php';
set_error_handler(['Drupal\error_page\ErrorPageErrorHandler', 'handleError']);
set_exception_handler([
  'Drupal\error_page\ErrorPageErrorHandler',
  'handleException',
]);
// Log the UUID in the Drupal logs.
$settings['error_page']['uuid']['enabled'] = TRUE;
// Don't inject the UUID into the logged message. Just keep it in variables.
$settings['error_page']['uuid']['add_to_message'] = FALSE;
// Your templates are located in path/to/templates, one level above the webroot.
$settings['error_page']['template_dir'] = DRUPAL_ROOT . '/../path/to/templates';
```

# Customizing the output page/message

By default the HTML markup page and message, displayed in case of error or
exception, are very simple but they can be can be customized. Probably the word
"themed" would be too much. In order to customize the output, the files
`markup/error_page.html` and `markup/error_message.html` should be copied and
edited in the location specified in `$settings['error_page']['template_dir']`.
Two variables can be used:

- `{{ uuid }}`: The error/exception UUID, if any.
- `{{ base_path }}`: The Drupal base path, as is returned by `base_path()`. This
  helps to build paths to images or other assets.

Don't forget to protect the templates location from public access.
