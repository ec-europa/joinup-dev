# Migration instructions

This module is intended for a one-time migration of the content from the
original Drupal 6 site to the new Drupal 8 site.

## Enable the required modules

```bash
$ drush en joinup_migrate migrate_drush -y
```

## Migrate the mapping table

First we need to import the data from an Excel sheet that contains mappings of
the content on the Drupal 6 site. The file can be found in the
`./resources/migrate` folder. This is imported into a MySQL database so its
records can be referenced quickly. It would be too slow to query the Excel file
directly for tens of thousands of records.

    $ drush migrate_drush_run mapping_table
