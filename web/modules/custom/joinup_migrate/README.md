# Migration instructions

This module is intended for a one-time migration of the content from the original Drupal 6 site to the new Drupal 8 site.


## Data used during the migration

The migration will use these resources:
* Data dump of the Drupal 6 database. Ask your friendly Joinup project
  maintainer for a sanitized dump that can be used for development and testing.
  Note that this file is not available in the repository because of the sheer
  size of the thing.
  The default location of the database dump is `./tmp/d6-joinup.sql`. The path
  and filename are configurable in the build properties (see below).
* An Excel sheet that contains mappings of the content on the Drupal 6 site.
  The file can be found in the `./resources/migrate` folder.
* The original files from the D6 version of Joinup. These are not currently
  available but will be made available in the future.


## Initial setup

In order to start a migration we will need to do some preparation:

1. Make sure you have the different resources available as outlined in the
   previous paragraph.
2. Put your local configuration into `build.properties.local`:

    ```
    # Migration configuration
    # -----------------------

    # Database settings.
    migration.db.name = my_db_name
    migration.db.user = my_db_user
    migration.db.password = my_db_pass
    migration.db.import_path = /my/path/to/d6-joinup.sql
    ```

3. Run the migration setup. Note that this should normally only be run once
   since it will write the migration database credentials to `settings.php`.
   Running this again will cause these credentials to be appended a second
   time which is useless.

    ```
    $ ./vendor/bin/phing setup-migration
    ```

4. Import the D6 database.

    ```
    $ ./vendor/bin/phing import-legacy-db
    ```


## Running the full migration

Once the setup is complete you can run a full migration with the following
command:

    ```
    $ ./vendor/bin/phing run-migration
    ```

You are now free to go for a walk or cook some fresh pasta, since this will
take some time.


## Running an individual migration

Individual parts of the migration can be run using drush. For this to work we
first need to enable the `joinup_migrate` module:

    ```
    $ cd web/
    $ ../vendor/bin/drush en joinup_migrate -y
    ```

Then we can run a single migration using `drush migrate_drush_run`:

    ```
    $ cd web/
    $ ../vendor/bin/drush migrate_drush_run mapping_table
    ```

To get a list of all available migrations, check the respective YAML files that
describe each individual migration:

    ```
    $ ls web/modules/custom/joinup_migrate/migrations
    ```
