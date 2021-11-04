# Install Joinup with Docker

## Requirements

* Docker
* docker-compose

## Getting started

### Environment variables

Create an `.env` file in the root directory where you should assign values for
environment variables containing sensitive data, such as passwords, tokens,
hashes or even custom preferences. These variables are not committed in VCS and
due to their nature should be manually set. Here is a list of variables that
need to be set in the `.env` file:

* `DRUPAL_HASH_SALT`
* `ASDA_URL`
* `ASDA_USER`
* `ASDA_PASSWORD`
* `DOCKER_RESTORE_PRODUCTION`

Other variable, dealing with custom developer preferences can also override
their `.env.dist` default value:

* `DISABLE_XDEBUG`
* `DISABLE_BLACKFIRE`

More information about these environment variables can be found in the
`.env.dist` file.

### Set up the config override

Configuration from `docker-compose.yml` can be overwritten in
`docker-compose.override.yml` file. Copy `docker-compose.override.yml.dist` over
to `docker-compose.override.yml`. Port mappings are not declared in the main
`docker-compose.yml` file because this is developer custom setting, so you'll
need to uncomment and edit, if case, the port mappings in the override file.
Port mapping for the web container is required for accessing the webpage from
the host machine so the override file is required in a normal workflow.

**macOS users**: As Docker has performance issues when the host machine is a
_macOS_ system, the setup needs more attention. Please read carefully all the
suggestions documented in the `docker-compose.override.yml.dist` file, specific
to _macOS_ users and fix the lines in `docker-compose.override.yml` file.

## Starting the containers

To start the containers, you can execute the following command from the project
root directory:

```bash
$ docker-compose up -d
```

This will automatically read the `docker-compose.yml` file as a source. The `-d`
options will start the containers in the background. If you omit the `-d`
option, `docker-compose` will run in the foreground.

## Stopping the containers

To stop the containers, you can use the command `docker-compose down` from the
same directory as the `docker-compose.yml`. Using this command, however, will
only stop the machine and will not destroy the volume that was created with it.
To clean the volume as well, use the `-v` parameter as `docker-compose down -v`.

## Before installing

By default, the `web` container starts with Xdebug and Blackfire.io enabled, in
order to support debugging. However, having them enabled while running Composer
install or installing the site will dramatically slow the operations. It's
recommended to enable them only when debugging and start the container with
Xdebug and Blackfire.io disabled. In order to do that, you should declare these
environment variables in your `.env` file:

```
DISABLE_XDEBUG="True"
DISABLE_BLACKFIRE="True"
```

During development the PHP modules can be manually disabled or enabled:

```bash
# Enable
$ docker-compose exec web phpenmod xdebug
$ docker-compose exec web phpenmod blackfire
# Disable
$ docker-compose exec web phpdismod xdebug
$ docker-compose exec web phpdismod blackfire
```

## Installing the codebase

Run the following command to install all packages in the vendor folder:

```bash
$ docker-compose exec --user www-data web composer install
```

### Known issues

#### Permission issues

There is a known issue related to the permissions of the host and container. In
order for the permissions to work properly, the user owning the files in the
host machine, the user executing commands in the container, the user running
Apache in the container and the user running the IDE, must all be able to
perform all necessary actions in the files. To workaround this, the user ID and
the group ID of the user that runs apache must be updated. To do this, before
building the containers, in the `docker-compose.override.yml` file, copy over
the web service, and replace the `image` entry with the following `build`

```yaml
services:
  web:
    build:
      context: './local'
      args:
        USER_ID: 1000
        GROUP_ID: 1000
```

where `./local` is a untracked directory (the name can be different) somewhere
on your system (this one is on the project root). Then, in the directory
declared above, create the following `Dockerfile` file.

```dockerfile
FROM fpfis/httpd-php-dev:7.4

ARG USER_ID=1000
ARG GROUP_ID=1000
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data
```

Change the user ID and group ID according to your needs. The value 1000 is the
value of the default login user of the Linux OS. To rebuild the containers,
first stop them by running from the host machine:

```bash
$ docker-compose down -v
```

and then recreate them by running:

```bash
$ docker-compose up -d --build -force-recreate
```

The above workaround will result the user `www-data` having the user ID and
group ID of the host login user. Now, running any command in the container as
`www-data`, will have the same effect on the files as running it locally.

#### GitHub authentication token

During Composer install, you might be asked for GitHub authentication token. If
you want to have your personal Git, Composer and SSH settings persist over the
web container, you can add the following entries in the `volumes` key under your
`web` container, in `docker-compose.override.yml` file:

```yaml
version: '3.8'
services:
  web:
    volumes:
      - ${PWD}:/var/www/html
      - ~/.gitconfig:/var/www/.gitconfig
      - ~/.gitignore:/var/www/.gitignore
      - ~/.composer:/var/www/.composer
      - ~/.ssh:/var/www/.ssh
```

Note that in the above snippet the first line is also part of the main
`docker-composer.yml`. This is because the `volumes` entry here will completely
override the parent entry and will not merge with it.

## Install an empty website

From the project root, run:

```bash
$ docker-compose exec web ./vendor/bin/run toolkit:install-clean
```
You can now access the website at `http://localhost/web` on the host machine, if
the port mapping is `80:8080`. If you define a different port mapping, such as
`8080:8080`, the website is accessible at `http://localhost:8080/web`.

## Install a cloned site

A _cloned site_ install is also restoring the databases from the production and
is running all update scripts.

### Downloading databases dump/snapshots

Before proceeding, make sure you set manually the `ASDA_*` environment variables
in `.env` file. The values should be provided by DevOps team.

#### Download all dumps/snapshots at once

```bash
$ docker-compose exec web ./vendor/bin/run ./vendor/bin/run dev:download-databases
```

#### Download only the MySQL dump

```bash
$ docker-compose exec web ./vendor/bin/run ./vendor/bin/run mysql:download-dump
```

#### Download only the Virtuoso snapshot

```bash
$ docker-compose exec web ./vendor/bin/run ./vendor/bin/run virtuoso:download-snapshot
```

#### Download only the Solr snapshot

```bash
$ docker-compose exec web ./vendor/bin/run ./vendor/bin/run solr:download-snapshot
```

**Note:** If you are changing from a clean install to a restored environment,
you'll need to destroy the volumes with `docker-compose down -v` (the `-v` is
the key option) in order to destroy the volumes, and subsequently
`docker-compose up -d` to rebuild.

### Tell Docker to install a cloned site

In order to make Docker start the database service containers (`mysql`, `sparql`
and `solr`) with production data, you should declare this in the `.env` file
before staring the services:

```
DOCKER_RESTORE_PRODUCTION=yes
```

After starting the containers, you may need to wait up to 1 minute because the
database dump and snapshots are being imported. Check the logs to find when
these operations are complete. If you plan to run all operations in a script, it
might be safe to start with a one minute _sleep_.

### Install the cloned site

After containers are up and got the chance to import from dump/snapshots, run
this sequence of commands:

```bash
$ docker-compose exec web ./vendor/bin/run dev:rebuild-environment
# Installs the development modules, such as `devel`.
$ docker-compose exec web ./vendor/bin/run dev:install-modules
```

But you can create your own Task Runner command, to include all steps, in the
`runner.yml` file, which is not under VCS control:

```yaml
commands:
  restore:
    - task: exec
      command: sleep
      arguments:
        - 60
    - task: run
      command: dev:rebuild-environment
    - task: run
      command: dev:install-modules
```

Then simply run this script from the host machine:

```bash
$ docker-compose exec web ./vendor/bin/run restore
```

## Accessing the containers

All containers are accessible through the command

```bash
$ docker-compose exec my_container_name "/bin/bash"
```

Depending on the container (and its base image), it might be that `/bin/bash` is
not available. In that case, `/bin/sh` and `sh` are good substitutes.

## Accessing the volumes

Some containers, like `solr`, define volumes that allow the container to
directly access files and folders on the host machine. These volumes are
constructed using the top-level `volumes` entry in the `docker-compose.yml` file
and inherit all properties from the containers. These volumes help retain the
data between builds. The since no directory is defined, the local driver will
set the default directory `/var/lib/docker/volumes/[volume identifier]`. This
directory is owned by the user that creates it within the container so the
directory listing will have to run with root privileges.

Please, note that the volume names, as with the docker services, will be
prefixed with the name of the folder that the project lies within e.g. if you
install the project on the `myproject` folder, the `mysql` volume, will be named
`myproject_mysql`.

## Useful commands

* When a service is not based on an image, but is built through a Dockerfile,
  the image is cached in `docker-compose` after first build. If changes are
  made, it can be rebuild using `docker-compose build <container> --no-cache`.
* To rebuild all containers on startup use the `--force-recreate` flag as such:
  `docker-compose up --force-recreate`.
* If a container persists still, use `docker-compose rm <container_id>` to
  remove it from the docker-compose cache and then recreate the containers.

## Debugging

The web container contains Xdebug. Please, refer to your IDE settings in order
to set up a debug session with Docker.

**Note:** _macOS_ users should set a different Xdebug configuration. See the
`docker-compose.override.yml.dist` file for details.
