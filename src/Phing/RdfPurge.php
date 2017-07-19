<?php

namespace DrupalProject\Phing;

/**
 * Class RdfPurge.
 */
class RdfPurge extends VirtuosoTaskBase {

  /**
   * Solr URL.
   *
   * @var string
   */
  protected $solrUrl;

  /**
   * Solr cores.
   *
   * @var string[]
   */
  protected $solrCores;

  /**
   * Purges the whole RDF backend.
   */
  public function main() {
    // Purge RDF backend.
    $this->execute('sparql DELETE { GRAPH ?g { ?entity ?field ?value } } WHERE { GRAPH ?g { ?entity ?field ?value . } };');
    // Purge Solr index.
    foreach ($this->solrCores as $core) {
      exec("curl '{$this->solrUrl}/$core/update?stream.body=<delete><query>*:*</query></delete>&commit=true'");
    }
  }

  /**
   * Set the Solr URL.
   *
   * @param string $solr_url
   *   The Solr URLs.
   */
  public function setSolrUrl($solr_url) {
    $this->solrUrl = trim($solr_url, ' /');
  }

  /**
   * Set the Solr cores.
   *
   * @param string[] $solr_cores
   *   A comma delimited list of Solr core names.
   */
  public function setSolrCores($solr_cores) {
    $this->solrCores = array_filter(array_map('trim', explode(',', $solr_cores)));
  }

}
