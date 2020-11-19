This folder contains cached responses from the Webtools Geocoding service. We
are using this service to perform the geolocation of event locations. The
service has a limited monthly quota so we should avoid doing unnecessary
requests. While running tests we will use these cached responses rather than
querying the live service.

This works by using the File Cache module and configuring it to store the cache
for Geocoder as files. This can be activated by enabling the 'filecache' module
and ensuring the following lines in settings.php:

```
$settings['cache']['bins']['geocoder'] = 'cache.backend.file_system';
$settings['filecache']['directory']['bins']['geocoder'] = '../tests/fixtures/webtools_geocoding_cache';
$settings['filecache']['strategy']['bins']['geocoder'] = 'persist';
```

Any requests that are done to the service will then be cached here. When
writing new Behat tests that create or edit events it is important to commit
these files so that they are available when running the test suite on the test
infrastructure.

In order to ensure that no requests go out to the live service during testing
there are Behat hooks implemented that will check that no new requests are
being cached when a test is being executed. If live requests are detected the
test will fail the test and the developer will be informed about this. For more
information see `\EventSubContext::beforeFeature()` and
`\EventSubContext::afterFeature()`.

If during development you are creating events manually while the File Cache is
active then you will see that the cached requests will pop up in this folder.
They should not be committed unless they are actually part of a Behat test. In
order to clean up any stray cache files, please execute this command:

```
$ git clean -fd ./tests/fixtures/webtools_geocoding_cache
```

Please note that in order to query the Webtools Geocoding service from outside
of the EC buildings you will need to ask the Webtools team to whitelist your IP
address.
