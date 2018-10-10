# Joinup on Docker

## Requirements
* Docker
* docker-compose

## Containers
To run Joinup in containers, we are using the following images:

#### fpfis/httpd-php-dev:7.1
This image is maintained by the fpfis team. More information can be
found in the [github repo](https://github.com/fpfis/httpd-php-dev/).

Default settings:
* Project root: `/var/www/html`.
* Web root: `/var/www/html/web`.
* docker.web.port: 8080

#### sass
The image used is [pablofelix/sass](https://hub.docker.com/r/pablofelix/sass/) and will compile the sass files during the build up.
The container exits after compilation.

#### mysql
The image used is percona/percona-server:5.6
The variables to be set are:
* MYSQL_ALLOW_EMPTY_PASSWORD
* MYSQL_USER
* MYSQL_PASSWORD
* MYSQL_DATABASE

The credentials should be the same as those declared in the settings.php file.

#### virtuoso
The image used is [tenforce/virtuoso](https://hub.docker.com/r/tenforce/virtuoso/)
The image comes with the SPARQL_UPDATE permission already set and the default database password.

#### selenium
The image used is [selenium/standalone-chrome-debug](https://hub.docker.com/r/selenium/standalone-chrome-debug/)

#### solr_published and solr_unpublished
These two services are identical and are using a local Dockerfile that extends the default container
[solr](https://hub.docker.com/_/solr/). This container is extended and comes with the search_api_solr configuration
files installed in /opt/docker-solr/configsets/drupal/conf.
Adding the directory of the `conf` directory as the last parameter of the solr-precreate, allows the files to be
installed in the new core:
```yaml
entrypoint:
    - docker-entrypoint.sh
    - solr-precreate
    - my_core_name
    - /opt/docker-solr/configsets/drupal
```
Note that the `conf` directory is not included in the entry.

## How to run

#### Run the containers
From the project root, run `docker-compose up`. This command will download, build and run all necessary containers.

#### Install the website
From the project root, run
```bash
docker-compose exec web "./vendor/bin/phing" "build-dev"
docker-compose exec web "./vendor/bin/phing" "install-dev"
```
#### Accessing the containers
All containers are accessible through the command
```bash
docker-compose exec my_container_name "/bin/bash"
```
Depending on the container (and its base image), it might be that `/bin/bash` is not available. In that case, `/bin/sh`
and `sh` are good substitutes.

#### Accessing the volumes
Some containers, like solr, create volumes that sync data from and towards the container. These volumes are constructed
using the top-level `volumes` entry in the docker-compose file and inherit all properties from the containers.

These volumes help retain the data between builds. The since no directory is defined, the local driver will set the
default directory, which is `/var/lib/docker/volumes/<volume identifier>`.

This directory is owned by the user that creates it within the container so the directory listing will have to run with
root privileges.

Please, note that the volume names, as with the docker services, will be prefixed with the name of the folder that the
project lies within e.g. if you install the project on the `myproject` folder, the `mysql` volume, will be named
`myproject_mysql`.

## Useful commands.
* When a service is not based on an image, but is built through a Dockerfile, the image is cached in docker-compose
after first build. If changes are made, it can be rebuild using `docker-compose build <container> --no-cache`.
* To rebuild all containers on startup use the `--force-recreate` flag as such: `docker-compose up --force-recreate`
* If a container persists still, use `docker-compose rm <container_id>` to remove it from the docker-compose cache and
then recreate the containers.
