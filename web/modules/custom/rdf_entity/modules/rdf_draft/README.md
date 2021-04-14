# SPARQL Graphs

In order to handle draft versions of the entities, the SPARQL storage supports
multiple graphs. For example, some projects might use two graphs:
* The first graph is the default graph provided by the `sparql_entity_storage`
  module and has `default` as ID.
* The `rdf_draft` module provides an extra graph, named `draft`.

A project may provide a different URI for each entity type's bundle. This URI
might have the pattern `<base url>/<bundle id>/<publish status>`. For example,
given a temporary base URL of `http://example.com`, and a bundlde `article`,
published entities are saved in `http://example.com/article/published` and their
corresponding draft versions are saved in `http://example.com/article/draft`.

To setup the graph settings, go to `/admin/config/sparql/graph/manage/draft` and
set which entity types should use the `draft` graph. If, for example, the
`rdf_entity` supports the `draft` graph, go to the `article` bundle config form,
at `/admin/structure/rdf_type/manage/article`, and, under
_SPARQL Entity Storage_ > _Graphs_, add the http://example.com/article/draft`
URI for _Draft (draft)_.

WARNING: Do not set the same graph URI for different graphs of different
bundles. The graph URI should be unique across all graphs, bundles and entity
types.

NOTE: For each extra graph other than the default, a new tab is generated
dynamically in the entity's primary actions that allows the user to view this
version. The default graph can be viewed in the default view.
