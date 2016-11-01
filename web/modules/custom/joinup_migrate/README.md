# Migration instructions

This module is intended for a one-time migration of the content from the original Drupal 6 site to the new Drupal 8 site.

## Enable the required modules

```bash
$ drush en joinup_migrate migrate_drush -y
```

## Migrate the mapping table

First we need to import the data from an Excel sheet that contains mappings of the content on the Drupal 6 site. The file can be found in the `./resources/migrate` folder. This is imported into a MySQL database so its records can be referenced quickly. It would be too slow to query the Excel file directly for tens of thousands of records.

    $ drush migrate_drush_run mapping_table

## Drupal 6 site webroot

In your `settings.php` you'll need to set the `joinup_migrate.source.root` to point to the webroot of the Drupal 6 site. A valid URL can be used too:

```php
$settings['joinup_migrate.source.root'] = 'http://example.com/current/d6/joinup';
```

If this setting is not configured, it defaults to `https://joinup.ec.europa.eu`.
