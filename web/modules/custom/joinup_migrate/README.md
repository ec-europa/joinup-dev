# Migration instructions

This module is intended for a one-time migration of the content from the
original Drupal 6 site to the new Drupal 8 site.

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
* The original files from the D6 version of Joinup. The files are located on our
  S3 Bucket. You should mount the `joinup2` S3 Bucket on your local file system
  or on the machine where the migration will take place. Get the S3 Bucket
  connection credentials from the team. Relevant info:
  * Bucket name: `joinup2`
  * Access Key ID
  * Secret Access Key


## Initial setup

In order to start a migration we will need to do some preparation:

1. Make sure you have the different resources available as outlined in the
   previous paragraph.

1. Mount the `joinup2` S3 Bucket on your local filesystem:

   Use the appropriate software. The most used is
   [FUSE-based file system backed by Amazon
   S3](https://github.com/s3fs-fuse/s3fs-fuse), which is supported on most
   ***nix** systems. Install the software following the
   [README.md](https://github.com/s3fs-fuse/s3fs-fuse/blob/master/README.md).

   Steps to mount files with [FUSE-based file system backed by Amazon
   S3](https://github.com/s3fs-fuse/s3fs-fuse):

   * Create a password file and secure it (the file name and location should be
     adapted on according to your preferences):
     ```bash
     $ echo MY_ACCESS_KEY_ID:SECRET_ACCESS_KEY > ~/.passwd-s3fs
     $ chmod 600 ~/.passwd-s3fs
     ```
   * Create a mount point directory:
     ```bash
     $ mkdir -p /path/to/mount/point
     ```
   * Mount the S3 Bucket files:
     ```bash
     $ s3fs joinup2 /path/to/mount/point -o passwd_file=~/.passwd-s3fs \
       -o allow_other -o umask=0002
     ```
     You can use a dedicated directory for cache in `-o use_cache=`.
   * Test the mount. Legacy D6 files should be under `joinupv2.0/` directory:
     ```bash
     $ ls /path/to/mount/point/joinupv2.0
     ```
     This directory should be the same as the `sites/default/files` directory.
   * Unmount the S3 Bucket:
     ```bash
     $ umount -f /path/to/mount/point
     ```

1. Put your local configuration into `build.properties.local`:

    ```
    # Migration configuration
    # -----------------------

    # Database settings.
    migration.db.name = my_db_name
    migration.db.import_path = /my/path/to/d6-joinup.sql

    # Files path. Point to the files directory of the legacy site.
    migration.source.files = /path/to/mount/point/joinupv2.0/files

    # How the migration will run: 'production' or 'test' mode.
    migration.mode = production
    ```

    Note that `migration.db.host`, `migration.db.port`, `migration.db.user` and
    `migration.db.password` are defaulting to the main database (D8) values as
    the migration is performing select queries that are joining tables across
    the two databases. For this reason both, source database and the destination
    database, should live on the same server and should be accessible by the
    same use. The MySQL user used to connect to the Drupal 8 site should be
    granted with read-only permissions against the D6 database, so he can read
    source data.

    Set the `migration.mode` to `test` if you want to run a migration only on a
    small subset of relevant data, covering most of the cases.

1. Run the migration setup. Note that this should normally only be run once
   since it will write the migration database credentials to `settings.php`.
   Running this again will cause these credentials to be appended a second
   time which is useless.

    ```
    $ ./vendor/bin/phing setup-migration
    ```

1. Import the D6 database.

    The source database (D6) should be imported *on the same server* as the
    destination database (D8) as the migration is performing select queries
    that are joining tables across the two databases. For this reason both,
    source database and the destination database, should live on the same server
    and should be accessible by the same use. The MySQL user used to connect to
    the Drupal 8 site should be granted with read-only permissions against the
    D6 database, so he can read source data. The MySQL user used to connect to
    the Drupal 8 site should be granted with read-only permissions against the
    D6 database, so he can read source data.

    ```
    $ ./vendor/bin/phing import-legacy-db
    ```


## Running the full migration

Once the setup is complete you can run a full migration with the following
command:

```bash
$ ./vendor/bin/phing run-migration
```

You are now free to go for a walk or cook some fresh pasta, since this will
take some time.


## Running an individual migration

Individual parts of the migration can be run using drush. For this to work we
first need to enable the `joinup_migrate` module:

```bash
$ cd web/
$ ../vendor/bin/drush en joinup_migrate -y
```

Then we can run a single migration using `drush migrate-import` or the shortcut
command `drush mi`:

```bash
$ cd web/
$ ../vendor/bin/drush mi mapping
```

Type `drush mi --help` to get a list of useful options.

To get a list of all available migrations, check the respective YAML files that
describe each individual migration:

```bash
$ ../vendor/bin/drush ms
```

Type `drush ms --help` to get a list of useful options.
