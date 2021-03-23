[![Build Status](https://travis-ci.org/ec-europa/rdf_entity.svg?branch=8.x-1.x)](https://travis-ci.org/ec-europa/rdf_entity)

Mainly, [RDF Entity](https://www.drupal.org/project/rdf_entity) provides an
entity type (`rdf_entity`) that uses the
[SPARQL](https://en.wikipedia.org/wiki/SPARQL) backend provided by [SPARQL
Entity Storage](https://www.drupal.org/project/sparql_entity_storage) module.
The entity type can be used as it is, can be extended or, simply, used as a good
use case of the [SPARQL Entity
Storage](https://www.drupal.org/project/sparql_entity_storage) module. 

### Updating from `1.0-alpha16` to `alpha17`

With `1.0-alpha17`, the SPARQL storage has been [split out, as a standalone
module](https://github.com/ec-europa/rdf_entity/issues/17). Moving services from
one module to the other is impossible with the actual Drupal core. See the
[related Drupal core issue](https://www.drupal.org/project/drupal/issues/2863986)
for details. As the
module is in alpha, we're not providing any update path but we recommend to
follow the next steps in order to update a server in production:

1. The update process is split in two consecutive deployments.
1. Install an empty version of the `sparql_entity_storage` module:
   ```
   $ composer require drupal/sparql_entity_storage:dev-empty-module
   ```
1. Enable the module.
1. Deploy to production.
1. Require `drupal/rdf_entity` with the new `1.0-alpha17` version and
perform a second deployment.
