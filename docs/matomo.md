# Matomo analytics

(formerly Piwik analytics)

Joinup uses the open source [Matomo](https://matomo.org/) web analytics platform
to gain insights in the usage and popularity of content.

In order to develop the analytics integration it is advisable to run a local
instance of Matomo. One easy way to get this up and running is by running a
containerized instance. For this you will need the following software:

- git - [https://git-scm.com/downloads](https://git-scm.com/downloads)
- docker-compose - [https://docs.docker.com/compose/install/](https://docs.docker.com/compose/install/)

Once the prerequisite software is installed, run the following command from the
project root to download and run Matomo:

```
$ ./vendor/bin/phing setup-matomo
```

This will download Matomo in ./vendor/matomo-org/matomo and start a running
instance at http://localhost:8000.

Now, visit the web UI at `http://localhost:8000`, or the host and port that were
configured previously in `./build.properties.local` and follow the installation
wizard. Use the values provided in console by the output of the previous
`./vendor/bin/phing setup-matomo` command. Check the 'Matomo' section in
`build.properties` for possible configuration options.

## Configuration

After Matomo has been installed, update your local Phing properties in
`build.properties.local` with the site ID and authentication token. These are
environment specific and can be found in the Matomo web interface:

```
# Website ID. This can be seen in the Matomo UI, at Administration > Websites >
# Manage.
matomo.website_id = 1

# Authentication token. This can be retrieved from the Matomo web interface at
# Administration > Platform > API > User authentication.
matomo.token = 0123456789abcdef0123456789abcdef
```

During the installation of the project these parameters will be written in the
`settings.local.php` file and used by Drupal to connect to the Matomo instance.

## Troubleshooting

### Nothing being registered

If your page visits are not being registered in Matomo, check the following:

* Adblockers or other privacy enhancing extensions might be blocking the
  requests.
* The browser's "Do not track" option might be enabled.

### Driver devicemapper failed

If you get the error `Driver devicemapper failed to remove root filesystem` when
stopping the Matomo containers, then shut down your web server and try again.

### Reinitializing Matomo

If you want to start from scratch using a fresh Matomo instance:

```
# Stop the running instance.
$ ./vendor/bin/phing stop-matomo

# Remove the local storage (run with sudo if needed).
$ sudo rm -rf ./vendor/matomo/matomo

# Set up a fresh instance.
$ ./vendor/bin/phing setup-matomo
```
