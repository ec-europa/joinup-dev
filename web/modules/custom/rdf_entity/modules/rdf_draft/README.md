# RDF graphs

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


### Usage: Query

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


### Usage: Storage

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


### Usage: Save/update entity

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


### Usage: Delete entity

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
