# Joinup on Docker

## Requirements
* Docker
* docker-compose

## Containers
To run Joinup in containers, we are using the following images:
* [fpfis/httpd-php-dev:7.1](https://github.com/fpfis/httpd-php-dev/)
* [pablofelix/sass](https://hub.docker.com/r/pablofelix/sass/)
* [percona/percona-server:5.6](https://hub.docker.com/r/percona/percona-server/)
* [tenforce/virtuoso](https://hub.docker.com/r/tenforce/virtuoso/)
* [selenium/standalone-chrome-debug](https://hub.docker.com/r/selenium/standalone-chrome-debug/)
* [solr:6](https://hub.docker.com/_/solr/). This container is extended and comes with the search_api_solr configuration
files installed in /opt/docker-solr/configsets/drupal/conf.

## How to run

#### Run the containers
From the project root, run `docker-compose up`. This command will download, build and run all necessary containers.

#### Install the website
From the project root, run
```bash
docker-compose exec --user www-data web ./vendor/bin/phing build-dev
docker-compose exec --user www-data web ./vendor/bin/phing install-dev
```

#### Accessing the containers
All containers are accessible through the command
```bash
docker-compose exec my_container_name "/bin/bash"
```
Depending on the container (and its base image), it might be that `/bin/bash` is not available. In that case, `/bin/sh`
and `sh` are good substitutes.

*IMPORTANT:* Depending on your configuration, it might be that you have to change the ownership of all the files in
order to have a successful installation and to be able to run the tests properly. For a possible solution, please, refer
to the section [Handling permissions](#handling-permissions)

#### Accessing the volumes
Some containers, like solr, create volumes that sync data from and towards the container. These volumes are constructed
using the top-level `volumes` entry in the docker-compose file and inherit all properties from the containers.

These volumes help retain the data between builds. The since no directory is defined, the local driver will set the
default directory, which is `/var/lib/docker/volumes/[volume identifier]`.

This directory is owned by the user that creates it within the container so the directory listing will have to run with
root privileges.

Please, note that the volume names, as with the docker services, will be prefixed with the name of the folder that the
project lies within e.g. if you install the project on the `myproject` folder, the `mysql` volume, will be named
`myproject_mysql`.

## XDEBUG
#### General configuration
In order to get XDEBUG working, run the following command depending on your environment:
* Linux: `sudo ip addr add 10.254.254.254/32 dev lo label lo:1`
* Mac: `sudo ifconfig en0 alias 10.254.254.254 255.255.255.0`
The above command needs to run every time you restart the computer.

The web container is set to use `10.254.254.254` as a remote host for xdebug and the port 9000.

#### PhpStorm
For PhpStorm, the procedure to create a debug environment is the same as with local servers with the only difference
that the mappings have to be set.
After you have created the server under `File | Settings | Languages & Frameworks | PHP | Servers`, enable the `Use path
mappings` option and set the absolute path on the server for your project root. By default, this is `/var/www/html`.

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
version: '2'
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

## Rebuild from existing databases
By default, the phing target `download-databases` sends the downloaded databases to the `tmp` folder of the project
root. The virtuoso dumps are stored in the sub directory `dump-virtuoso` and the mysql dump is located in the `tmp`
folder itself.
In the project root, there is also the docker-compose.prod_db.yml which contains overrides for restoring the databases
according to the image requirements. What it does, is that it maps the dump.sql (the mysql dump) within the startup
directory of the mysql image, and the virtuoso dumps within the startup directory of the virtuoso image.

To start the machines with the databases restored, run the following command:  
`docker-compose -f docker-compose.yml -f docker-compose.prod_db.yml up`  
and the images will be started.

After the images have been built and the databases have been restored, run the following command to execute the
updates  
`docker-compose exec --user www-data web ./vendor/bin/phing execute-updates`.

Finally, in order to run a full re-index of the site, run the command  
`docker-compose exec --user www-data web ./vendor/bin/phing reindex-apache-solr`.

**IMPORTANT**: All images start normally and the web server is available almost immediately. However, mysql container will
not start until the backup is restored so for the first few minutes, depending on the size of the database dump, the web
server will receive connection denials. Please, wait until the mysql import is finishes before accessing the site.

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
`docker-compose -f docker-compose.yml -f resources/docker/docker-compose.mac.yml up`  
in order to have the volumes set up correctly.
