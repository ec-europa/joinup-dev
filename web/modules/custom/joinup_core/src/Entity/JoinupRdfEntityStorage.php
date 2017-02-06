<?php

namespace Drupal\joinup_core\Entity;

use Drupal\rdf_entity\Entity\RdfEntitySparqlStorage;

/**
 * Workaround the issue with bundles having the same type.
 *
 * @see https://webgate.ec.europa.eu/CITnet/jira/browse/ISAICP-3126
 */
class JoinupRdfEntityStorage extends RdfEntitySparqlStorage {

  /**
   * {@inheritdoc}
   */
  protected function getQueryServiceName() {
    return 'entity.query.joinup_rdf_sparql';
  }

}
