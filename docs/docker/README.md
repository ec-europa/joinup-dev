# Joinup on Docker

## Requirements
* Docker
* docker-compose

## Getting started
### Environment variables
Create an `.env` file in the root directory where you can place all your config overridden.
More information about the settings can be found at the .env.dist file. Copy the settings you want altered.

### Set up a random hash salt.
From the host environment, run
```bash
docker-compose exec web echo "DRUPAL_HASH_SALT=$(cat /dev/urandom | LC_ALL=C tr -dc 'a-zA-Z0-9+/' | fold -w ${1:-55} | head -n 1)" >> .env
```
to generate the `DRUPAL_HASH_SALT` variable in the `.env` file. Note, that this variable, is required for Drupal but the
value can be arbitrary. You do not need to set a specific value e.g. to rebuild from the production database

### Set up the config override
Copy `docker-compose.override.yml.dist` over to `docker-compose.override.yml`. This file will include the overrides for
the `docker-compose.yml` file.
Uncomment the lines your wish to change and also uncomment the ports so that the web container is accessible over the
web.

## Starting the containers
To start the containers, you can execute the following command from the project root directory:
```bash
docker-compose up -d
```
This will automatically read the `docker-compose.yml` file as a source. The `-d` command will start the containers in
the background. You can ommit the `-d` parameter and docker-compose will run in the foreground.

## Stopping the containers
To stop the containers, you can use the command `docker-compose down` from the same directory as the docker-compose.yml.
Using this command however, will only stop the machine and will not destroy the volume that was created with it. To
clean the volume as well, use the `-v` parameter as `docker-compose down -v`.

## Installing dependencies
Run the following command to install all packages in the vendor folder:
```bash
docker-compose exec --user www-data web composer install
```
A Github authentication token will be required to download all packages.
**Note:** If you have permission issues, please check known issue [Apache daemon user](#apache-daemon-user).
**Note 2:** For the authentication token, you can map your local directories to the container in order to reuse all keyes
and tokens from your local account. Check [Reuse local configuration](#reuse-local-configuration) for more info.

## Before installing
Before installing, check the [Known issues and tips](#known-issues-and-tips) for possible issues and optimizations.

## Install the website
From the project root, run:
```bash
docker-compose exec --user www-data web ./vendor/bin/run toolkit:install-clean
```
You can now access the website at `http://localhost` or the corresponding endpoint if you have overridden the
settings.

## Accessing the containers
All containers are accessible through the command
```bash
docker-compose exec my_container_name "/bin/bash"
```
Depending on the container (and its base image), it might be that `/bin/bash` is not available. In that case, `/bin/sh`
and `sh` are good substitutes.

**Note:** Depending on your configuration, it might be that you have to change the ownership of all the files in
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

## Debugging tests
The web container contains XDEBUG version `^2.9`. Please, refer to your IDE settings in order to set up a debug session
with docker.

## Rebuild from existing databases
Follow the instructions from the main README in order to set up the ASDA credentials.
Download both the SPARQL and SQL database dumps using the following command:
```
$ docker-compose exec --user www-data web php -d memory_limit=-1 ./vendor/bin/run dev:download-databases
```
**Note:** If you are changing from a clean install to a restored environment, you need to destroy the volumes with
`docker-compose down -v` (the `-v` is the key) in order to destroy the volumes,
and subsequently `docker-compose up -d` to rebuild.

## Launch containers using production databases
To start the containers using the production database, in your `.env` file, set the variable `DOCKER_RESTORE_PRODUCTION`
to yes. The fire up the containers.
After the images have been built, and the databases have been restored, run the following command to execute the
updates:
```
$ docker-compose exec --user www-data web ./vendor/bin/run toolkit:run-deploy
```
**Note**: All images start normally, and the web server is available almost immediately. However, mysql container
will not start until the backup is restored so for the first few minutes, depending on the size of the database dump,
the web server will receive connection denials. Please, wait until the mysql import is finishes before accessing the
site.

## Known issues and tips
### Apache daemon user
There is a known issue related to the permissions of the host and container. In order for the permissions to work
properly, the user owning the files in the host machine, the user executing commands in the container, the user running
Apache in the container and the user running the IDE, must all be able to perform all necessary actions in the files.
To workaround this, the user ID and the group ID of the user that runs apache must be updated. To do this, before
building the containers, in the `docker-compose.override.yml` file, copy over the web service, and replace the
`image` entry with the following `build`
```yaml
services:
  web:
    build:
      context: './local'
      args:
        USER_ID: 1000
        GROUP_ID: 1000
```
where `./local` is a untracked directory (the name can be different) somewhere on your system (this one is on the
project root).
Then, in the directory declared above, create the following `Dockerfile` file.
```dockerfile
FROM fpfis/httpd-php-dev:7.1

ARG USER_ID=1000
ARG GROUP_ID=1000
RUN usermod -u ${USER_ID} www-data && \
    groupmod -g ${GROUP_ID} www-data
```
Change the user ID and group ID according to your needs. The value 1000 is the value of the default login user of the
Linux OS.
To rebuild the containers, first stop them by running from the host machine
```bash
docker-compose down -v
```
and then recreate them by running
```bash
docker-compose up -d --build -force-recreate
```
The above workaround will result the user `www-data` having the user ID and group ID of the host login user.
Now, running any command in the container as `www-data`, will have the same effect on the files as running it locally.

### Reuse local configuration
If you want to have your personal git, composer and ssh settings persist over the web container,
you can add the following entries in the `volumes` key in your `web` container.
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
Note that in the above snippet the first line is also part of the main `docker-composer.yml`. This is because
the `volumes` entry here will completely override the parent entry and will not merge with it.

### XDEBUG and Blackfire
The web container includes the `phpenmod` and `phpdismod` commands that can enable and disable PHP modules.
Two of the modules can slow the processes very much, XDEBUG and Blackfire. You can enable and disable them by running
the following commands
```bash
# Enable xdebug.
docker-compose exec web phpenmod xdebug
# Enable blackfire.
docker-compose exec web phpenmod blackfire

# Disable xdebug.
docker-compose exec web phpdismod xdebug
# Disable blackfire.
docker-compose exec web phpdismod blackfire
```
It is highly recommended that these modules are disabled for long processes, e.g. the installation of the website.
