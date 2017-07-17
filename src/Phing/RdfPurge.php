<?php

namespace DrupalProject\Phing;

/**
 * Class RdfPurge.
 */
class RdfPurge extends VirtuosoTaskBase {

  /**
   * Purges the whole RDF backend.
   */
  public function main() {
    // Purge RDF backend.
    $this->execute('sparql DELETE { GRAPH ?g { ?entity ?field ?value } } WHERE { GRAPH ?g { ?entity ?field ?value . } };');
    // Purge Solr index.
    foreach (['drupal_published', 'drupal_unpublished'] as $core) {
      exec('curl "http://localhost:8983/solr/' . $core . '/update?stream.body=<delete><query>*:*</query></delete>&commit=true"');
    }
  }

}
