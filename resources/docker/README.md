# Joinup on Docker

## Requirements
* Docker
* docker-compose

## Containers
To run Joinup in containers, we are using the following images:
* [php:7.1.24-apache-jessie](https://hub.docker.com/_/php/) which is extended with configuration based on the [docker
for drupal](https://github.com/docker-library/drupal) and the [fpfis/httpd-php](https://github.com/fpfis/httpd-php)
images.
* [percona/percona-server:5.6](https://hub.docker.com/r/percona/percona-server/)
* [tenforce/virtuoso](https://hub.docker.com/r/tenforce/virtuoso/)
* [selenium/standalone-chrome-debug](https://hub.docker.com/r/selenium/standalone-chrome-debug/)
* [solr:6](https://hub.docker.com/_/solr/). This container is extended and comes with the search_api_solr configuration
files installed in /opt/docker-solr/configsets/drupal/conf.

## Getting started

### Starting the containers
To start the containers, you can execute the following command from the project root directory:

```bash
$ docker-compose up -d
```

This will automatically read the `docker-compose.yml` file as a source. The `-d` command will start the containers in
the background. If you need to debug your build, ommit the `-d` parameter and docker-compose will run in the foreground.
You can specify more than one sources in a counter versa priority using the -f parameters. For example
`docker-compose -f docker-compose.yml -f docker-compose.local.yml up -d` will start the containers with the variables
from the second source, overriding the first. More of that on
'[Override default configuration](#override-default-configuration)'.

### Stopping the containers
To stop the containers, you can use the command `docker-compose down` from the same directory as the docker-compose.yml.
Using this command however, will only stop the machine and will not destroy the volume that was created with it. To
clean the volume as well, use the `-v` parameter as `docker-compose down -v`.

### Prepare the environment
Run the following command to install all packages in the vendor folder:

```bash
docker-compose exec --user www-data web composer install
```

### Install the website
From the project root, run:

```bash
docker-compose exec --user www-data web ./vendor/bin/run toolkit:build-dev
docker-compose exec --user www-data web ./vendor/bin/run toolkit:install-clean
```

You can now access the website at `http://localhost:8080` or the corresponding endpoint if you have overridden the
settings.

## Accessing the containers
All containers are accessible through the command
```bash
docker-compose exec my_container_name "/bin/bash"
```
Depending on the container (and its base image), it might be that `/bin/bash` is not available. In that case, `/bin/sh`
and `sh` are good substitutes.

*IMPORTANT:* Depending on your configuration, it might be that you have to change the ownership of all the files in
order to have a successful installation and to be able to run the tests properly. For a possible solution, please, refer
to the section [Handling permissions](#handling-permissions)

## Accessing the volumes
Some containers, like solr, define volumes that allow the container to directly access files and folders on the host
machine. These volumes are constructed using the top-level `volumes` entry in the docker-compose file and inherit all
properties from the containers.

These volumes help retain the data between builds. The since no directory is defined, the local driver will set the
default directory, which is `/var/lib/docker/volumes/[volume identifier]`.

This directory is owned by the user that creates it within the container so the directory listing will have to run with
root privileges.

Please, note that the volume names, as with the docker services, will be prefixed with the name of the folder that the
project lies within e.g. if you install the project on the `myproject` folder, the `mysql` volume, will be named
`myproject_mysql`.

## Useful commands
* When a service is not based on an image, but is built through a Dockerfile, the image is cached in docker-compose
after first build. If changes are made, it can be rebuild using `docker-compose build <container> --no-cache`.
* To rebuild all containers on startup use the `--force-recreate` flag as such: `docker-compose up --force-recreate`
* If a container persists still, use `docker-compose rm <container_id>` to remove it from the docker-compose cache and
then recreate the containers.

## Override default configuration
In your local environment, on the project root, you can create a second docker-compose yml file called
`docker-compose.local.yml`. This file is ignored by git. In that file, you can set up services overrides and setup your
own settings on the environment. Below is an example of the docker-compose.override.yml that allows the user that runs
apache to have its UID and GID changed (the group's id changes) and sets up the server to run on port 80.
```yaml
version: '3.4'
services:
  web:
    build:
      args:
        DAEMON_UID: "1000"
        DAEMON_GID: "1000"
    expose:
      - "80"
    ports:
      - "80:80"
    environment:
      PORT: "80"
```
The rest of the service will get the properties from the original composer file.

To run the containers by reading all overrides, use the following command: `docker-composer -f docker-compose.yml
-f docker-compose.local.yml up`. Please, note, that the last file in the command has bigger priority. More than one
overrides can be provided.

As an example of usage, if you want to have your personal git, composer and ssh settings persist over the web container,
you would have to have something like the following override file:
```yaml
version: '3.4'
services:
  web:
    volumes:
      - .:/var/www/html
      - ./build.docker.main.xml:/var/www/html/build.xml
      - ~/.gitconfig:/var/www/.gitconfig
      - ~/.gitignore:/var/www/.gitignore
      - ~/.composer:/var/www/.composer
      - ~/.ssh:/var/www/.ssh
```
Note that in the above snippet the first two lines are also part of the main `docker-composer.yml`. This is because
the `volumes` entry here will completely override the parent entry and will not merge with it.

For extra configuration, the folder `resources/docker/local` is excluded in git and can be used to host local settings.
You can use the volumes section above to add your own preferences to the containers. A good example is provided in the
XDEBUG section below.

## XDEBUG
### PhpStorm
For PhpStorm, the procedure to create a debug environment is the same as with local servers with the only difference
that the mappings have to be set.
After you have created the server under `File | Settings | Languages & Frameworks | PHP | Servers`. Create a server for
localhost and port 8080, or the port that you with the container to run from. By default, the web container will be
reachable in port 8080. Enable the `Use path mappings` option and set the absolute path on the server for your project
root. By default, this is `/var/www/html`.

### Overriding default xdebug configuration
Taking a look at the web container's Dockerfile you will see some default settings for the xdebug. These settings are
overridable in the following ways:
* Using the `XDEBUG_CONFIG=` environment variable: This needs to be used every time a php command is run and all
overrides must be passed in. e.g.
```bash
XDEBUG_CONFIG="remote_autostart=1" php myscript.php
```
* Using a local override: The php configuration files are in the `/usr/local/etc/php` directory within the web container
and can be overridden on demand. The xdebug settings are placed in the `usr/local/etc/php/conf.d/95-xdebug.ini` file.
Please, note that the `95` in the beginning of the file declares the priority within the `conf.d` directory. In order to
provide overrides for xdebug, you will need to provide a file starting with a number higher than `95`. For example, to
override the `remote_autostart` setting as we did above, you can do by:
  * Create a file named e.g. `xdebug.local.ini` in the `resources/docker/local` directory.
  * Add `remote_autostart=1` as contents of the file.
  * Use the following configuration in your docker-compose.local.yml file:
```yaml
version: '3.4'
services:
  web:
    volumes:
      - .:/var/www/html
      - ./build.docker.main.xml:/var/www/html/build.xml
      - ./resources/docker/local/xdebug.local.ini:/usr/local/etc/php/conf.d/97-xdebug.ini
```
Our file has a priority of `97` which means that it will be loaded after the `95-xdebug.ini` and thus our settings will
persist over the default file.

### Debugging tests
After setting up XDEBUG according to your needs, you need to be able to use it when debugging tests. To run the debugger
you need to provide the `XDEBUG_CONFIG=` environment variable when running a script. For example, to run a behat test,
run the following command from within the container from the doc root:
```bash
XDEBUG_CONFIG= ./vendor/bin/behat -c ./tests/behat.yml
```
By default, there is no need to pass any settings to the XDEBUG_CONFIG environment variable as all settings are drawn
from the xdebug ini files.

## Rebuild from existing databases

### Obtain credentials
The production databases are stored on a private server. In order to get access, ask your friendly project manager for
the paths and credentials. Store them in the `build.properties.local` file in the following properties:

- `exports.s3.key`
- `exports.s3.secret`

### Download databases
Download both the SPARQL and SQL database dumps using the following command:

```
$ docker-compose exec --user www-data web php -d memory_limit=-1 ./vendor/bin/run dev:download-databases
```

By default the downloaded databases are stored in the `tmp` folder which is located in the project root. The virtuoso
dumps are stored in the sub directory `dump-virtuoso` and the mysql dump is located in the `tmp` folder itself.

Note that the PHP memory limit is being disabled. Phing uses a large amount of memory during the download.

### Launch containers using production databases
To start the machines with the databases restored, first make sure your docker containers are down:

```
$ docker-compose down
```

Then start them using the alternative docker-compose file with support for database dumps:

```
$ docker-compose -f docker-compose.yml -f docker-compose.db.yml up -d
```

The `docker-compose.database.yml` file contains overrides for restoring the databases according to the image
requirements. What it does, is that it maps the dump.sql (the mysql dump) within the startup directory of the mysql
image, and the virtuoso dumps within the startup directory of the virtuoso image.

**Note:** As you can see in `docker-compose.db.yml`, the dumps need to be placed in a specific folder in the container.
You can alter the configuration using your override to draw the dump from anywhere in the host, but the container target
must remain the same. The download of the database is *not* automatic. You need to download and place them in the
specific directories manually or by using the `dev:download-databases` runner command. In any case, the dumps must be
placed as described in the volumes entry in `docker-compose.prod_db.yml`.

After the images have been built and the databases have been restored, run the following command to execute the
updates:

```
$ docker-compose exec --user www-data web ./vendor/bin/run toolkit:run-deploy --sequence-file=runner/deploy.yml --sequence-key=default
```

Finally, in order to run a full re-index of the site, run the command:

```
$ docker-compose exec --user www-data web ./vendor/bin/run solr:reindex
```

**IMPORTANT**: All images start normally and the web server is available almost immediately. However, mysql container
will not start until the backup is restored so for the first few minutes, depending on the size of the database dump,
the web server will receive connection denials. Please, wait until the mysql import is finishes before accessing the
site.

## Handling permissions
The web container is having the apache service configured to run as user www-data and group www-data. By default, both
the UID and GID of this user/group is 82. Docker does not offer a solution for mimicking a user from the host to the
container so it might be a bit annoying when testing live while developing.

As a solution the [Dockerfile](web/Dockerfile) is offering a way to change the UID and GID of the www-data user while
the original docker container (fpfis/httpd-php-dev) is offering a way to override the user and group itself. While a new
user is not created, if, for example your host user, owner of the files, is having the UID 1000 and its group GID is
also 1000, you can override these settings and allow all files to be owned by you. Like this, ownership and permission
issues related to the containers should not occur. The way to do this is described above in the section '[Override
default configuration](#override-default-configuration)'.

## Running in Mac
Mac users have to define the volumes in a different way than linux. For the default docker-compose profile of Joinup,
there is also a [docker-compose.mac.yml](./docker-compose.mac.yml) file provided in the `resources/docker` directory.
Mac users should start the containers by running  
`docker-compose -f docker-compose.yml -f resources/docker/docker-compose.mac.yml up -d`
in order to have the volumes set up correctly.
