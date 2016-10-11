# Joinup website

This is the source code for https://joinup.ec.europa.eu/

[![Build Status](https://status.continuousphp.com/git-hub/ec-europa/joinup-dev?token=77aa9de5-7fef-40bc-8c48-d6ff70fba9ff)](https://continuousphp.com/git-hub/ec-europa/joinup-dev)

Joinup is a collaborative platform created by the European Commission and
funded by the European Union via the [Interoperability Solutions for European
Public Administrations (ISA)](http://ec.europa.eu/isa/) Programme.

It offers several services that aim to help e-Government professionals share
their experience with each other.  We also hope to support them to find,
choose, re-use, develop and implement interoperability solutions.

The Joinup platform is developed as a Drupal 8 distribution, and therefore
tries to follow the 'drupal-way' as much as possible.

You are free to fork this project to host your own collaborative platform.
Joinup is licensed under the
[EUPL](https://joinup.ec.europa.eu/community/eupl/og_page/eupl), which is
compatible with the GPL.

## Contributing
See our [contributors guide](.github/CONTRIBUTING.md).

## Running your own instance of Joinup

### Requirements
* A regular LAMP stack
* Virtuoso (Triplestore database)
* SASS compiler
* Apache Solr

### Dependency management and builds

We use Drupal composer as a template for the project.  For the most up-to-date
information on how to use Composer, build the project using Phing, or on how to
run the Behat test, please refer directly to the documention of
[drupal-composer](https://github.com/drupal-composer/drupal-project).

### Initial setup

* Clone the repository.

    ```
    $ git clone https://github.com/ec-europa/joinup-dev.git
    ```

* Use [composer](https://getcomposer.org/) to install the dependencies.

    ```
    $ cd joinup-dev
    $ composer install
    ```

* Install Solr. If you already have Solr installed you can configure it manually
  by [following the installation
  instructions](http://cgit.drupalcode.org/search_api_solr/plain/INSTALL.txt?h=8.x-1.x)
  from the Search API Solr module. Or you can execute the following command to
  download and configure a local instance of Solr. It will be installed in the
  folder `./vendor/apache/solr`.

    ```
    $ ./vendor/bin/phing setup-apache-solr
    ```

* Install Virtuoso. For basic instructions, see [setting up
  Virtuoso](/web/modules/custom/rdf_entity/README.md). During installation some
  RDF based taxonomies will be imported from the `resources/fixtures` folder.
  Make sure Virtuoso can read from this folder by adding it to the `DirsAllowed`
  setting in your `virtuoso.ini`. For example:

    ```
    DirsAllowed = /var/www/joinup/resources/fixtures, /usr/share/virtuoso-opensource-7/vad
    ```

* Install the official [SASS compiler](https://github.com/sass/sass). This
  depends on Ruby being installed on your system.

    ```
    $ gem install sass
    ```

* Point the document root of your webserver to the 'web/' directory.

### Create a local build properties file
Create a new file in the root of the project named `build.properties.local
using your favourite text editor:

```
$ vim build.properties.local
```

This file will contain configuration which is unique to your development
machine. This is mainly useful for specifying your database credentials and the
username and password of the Drupal admin user so they can be used during the
installation.

Because these settings are personal they should not be shared with the rest of
the team. Make sure you never commit this file!

All options you can use can be found in the `build.properties.dist` file. Just
copy the lines you want to override and change their values. Do not copy the
entire `build.properties.dist` file, since this would override all options.

Example `build.properties.local`:

```
# The location of the Composer binary.
composer.bin = /usr/bin/composer

# The location of the Virtuoso console (Debian / Ubuntu).
isql.bin = /usr/bin/virtuoso-isql
# The location of the Virtuoso console (Arch Linux).
isql.bin = /usr/bin/virtuoso-isql
# The location of the Virtuoso console (Redhat / Fedora).
isql.bin = /usr/local/bin/isql

# SQL database settings.
drupal.db.name = my_database
drupal.db.user = root
drupal.db.password = hunter2

# SPARQL database settings.
sparql.user = my_username
sparql.password = qwerty123

# Admin user.
drupal.admin.username = admin
drupal.admin.password = admin

# The base URL to use in tests.
drupal.base_url = http://joinup.local

# Verbosity of Drush commands. Set to 'yes' for verbose output.
drush.verbose = yes
```


### Build the project

Execute the [Phing](https://www.phing.info/) target `build-dev` to build a
development instance, then install the site with `install-dev`:

```
$ ./vendor/bin/phing build-dev
$ ./vendor/bin/phing install-dev
```


### Run the tests

Run the Behat test suite to validate your installation.

```
$ cd tests
$ ./behat
```

Also run the PHPUnit tests, from the web root.

```
$ cd web
$ ../vendor/bin/phpunit
```


### RDF graphs

In order to handle draft versions of the entities, the rdf SPARQL storage supports multiple graphs.
In Joinup, 2 graphs are used.
* The first graph is the default graph provided by the rdf_entity module and is called 'default'.
* The rdf_draft module provides an extra graph which is named 'draft'.
The URI of each graph for each bundle of the rdf entities.

For the joinup project, we provide a different uri for each entity type's bundle.
This uri has the form of `<base url>/<bundle id>/<publish status>`.
For example, given a temporary base url of `http://joinup.eu`, published collections are saved in
`http://joinup.eu/collection/published` and their corresponding draft versions are saved in
`http://joinup.eu/collection/draft`.

To setup the graph settings, go to /admin/config/rdf_entity/draft-settings.
From there, you can set which bundles have a draft graph available and which is the default save graph.
For joinup, the default save graph is the graph where we keep the published version of the entities.

When the draft options are set, navigate to each of your rdf entity bundle settings and set the graph uri
for each of the available graphs (currently, all graphs must have a value).

WARNING: Do not set the same graph for different graph types of different bundles. This will break the functionality.
For example, for bundle1 and bundle2, if you set the published graph uri of bundle1 to be the same with the draft graph
uri of bundle2 and you request the draft version of the entity from bundle1, you will receive the published
version instead. Look below to the loading of the entity for the reason.

NOTE: For each extra graph other than the default, a new tab is generated dynamically in the entity's primary actions
that allows the user to view this version. The default graph can be viewed in the default view.

### RDF graphs storage

Graphs are handled by the `GraphHandler` service which is injected in the Query and the RdfEntitySparqlStorage classes.
There is a number of methods offered to handle the graphs.
There are three categories of graph variables in the following priority sequence:
1. Target graph: This is saved in the service and is meant to be used in order to force an override on the graph that
the entity will be saved.
2. Entity graph: This is stored within the entity in the `graph` field. If no override is provided, this is the variable
that determines the graph that the entity is saved in.
3. Request graphs: These are the graphs that the storage and the query are going to look into for the entity. This
variable is an array of graphs as it declares a priority from which the entity will be loaded.
For example, if we are looking at the draft graph tab, the request graphs will be set to only look into the draft graphs
as this tab is only meant to show the draft version. On the other hand, if we are looking the normal view then there is
a sequence of actions followed
    1. If there is a published version (default graph), show this version.
    2. If there is not a published version, fallback to the next available graph.
    3. If there is a version in the next graph, show this version.
    4. Repeat second step.

For this reason, the priority should set as `['default', 'draft']`.

In joinup, the view of the entity loads with the priority set as above. The edit of the entity is set with the opposite
priority. This is because if we want to edit the entity, we probably care for the draft version and not the published.
When an entity is moved from draft graph to published, the draft version is deleted.
No revision history is kept at the moment.


##### Usage: Query

To query an entity in SPARQL, you need to use the default drupal query as follows:

```
$query = \Drupal::service('entity_type.manager')->getStorage('rdf_entity')->getQuery();
```
To further filter by graph, it is enough to call the

```
$query->setGraphType(['default', 'draft']);
```
This is where the filter applies. You cannot currently filter by the `condition()` method.

NOTICE: The results will have only one graph per entity and this will be the first available graph. With the
`setGraphType()` method, you set the priority, not the list of graphs to retrieve from. Also, all loaded entities will
be affected by the priority provided in the setGraphType. If you want to set the priority only for a specific entity,
given that you have the id of this entity, you can do it by setting the priority to the GraphHandler service itself
through the storage class.


##### Usage: Storage

To load the entity from the storage you will first need to get the storage class. A good way is the default
way:

```
$storage = \Drupal::service('entity_type.manager')->getStorage('rdf_entity');
```
Then, you can use the `setRequestGraphs($entity_id, array $graph_types)` to set the graphs. Note that for the storage
class, this method requires an entity id. This is because multiple entities can be loaded in a single request or the
same entity can be loaded more than once. This means that we cannot have a global priority, nor reset the priority after
the entity is loaded.
The difference with the `$query->setGraphType()` above, is that the query saves the graph locally for the query. That
means that all loaded entities are affected by it.
For entities that do not have a specific set of graphs in the query, the normal priority is being used (first from the
'default' graph and then from the 'draft' graph).


##### Usage: Save/update entity

The default way of determining where the entity will be saved, is by setting the `graph` field to the entity.
When an entity is loaded, this dynamic field is pre-filled with the graph where it was loaded from. That means that if
nothing interferes in the process, the entity will be saved in the same graph.

Unlike the loading, save does not have priorities when it comes to graphs. The entity is saved in the graph defined in
the `graph` field of the entity.
There is an option to force an override by setting the `$target_graph` variable in the GraphHandler service. This
variable accepts a single graph id and affects all saves taking place in the same request.
To force an override, use
```
\Drupal::service('entity_type.manager')->getStorage('rdf_entity')->setSaveGraph($graph);
```
This is a shortcut to the Graph handler service.


##### Usage: Delete entity

Like the save functionality above, the delete can happen using the graph field of the entity. Also, like above, there is
no priority here as well. The entity is deleted _only_ from the graph defined in the entity itself.
To completely delete an entity and all its versions, you can run something like
```
/** @var \Drupal\rdf_entity\Entity\RdfEntitySparqlStorage $storage */
$storage = \Drupal::service('entity_type.manager')->getStorage('rdf_entity');
// Remove any solutions that were created.
foreach ($this->solutions as $solution) {
  foreach ($storage->getGraphHandler()->getEntityTypeEnabledGraphs() as $graph) {
    $storage->getGraphHandler()->setTargetGraphToEntity($solution, $graph);
    $solution->delete();
  }
}
```

### Joinup notification module

The joinup notification module is a custom module that depends only on message,
message_notify, og and state machine.

The way it works is by saving the notifications in a settings file in the
installation folder of the module.
There are two arrays in the config settings of joinup notification, the
transition_notifications and the delete_notifications.

The transition notifications are message ids indexed by the role, transition
and workflow group as shown below.
```
$config = [
  <workflow_group_id> => [
    <transition> => [
      <role> => [
        <message_id>
      ]
    ]
  ]
]
```
The delete_notifications array is not depending on states so it is has the
same approach but uses the entity type id instead of the workflow group and
the bundle instead of the transition.
```
$config = [
  <entity_type_id> => [
    <entity_bundle> => [
      <role> => [
        <message_id>
      ]
    ]
  ]
]
```
There can be multiple message ids and the roles are either site-wide or og.

All notifications are handled by a single event handler which is iterating over
the appropriate array, sending the messages to their corresponding users (those
found with the provided role).

To add a new notification, use the following procedure:

* Export the template of the notification message to the joinup_notification/config/install directory.
* Update the joinup_notification/config/install/joinup_notification.settings.yml
file's appropriate array according to the information above, to include the new
notification transition. Use the message id from the first step.
* If it is a transition notification, update the $keys array in the
joinup_notification/EventSubscriber/WorkflowTransitionEventSubscriber::getSubscribedEvents
method.
* Provide a behat test.

If you set the information in a correct way, the notification should be sent
either in the event subscriber, or in the joinup_notification_entity_delete
hook.

### Frontend development

See the [readme](web/themes/joinup/README.md) in the theme folder.
