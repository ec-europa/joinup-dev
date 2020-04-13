<?php

declare(strict_types = 1);

namespace Drupal\joinup_seo;

use Drupal\rdf_entity\RdfInterface;
use Drupal\sparql_entity_storage\SparqlSerializerInterface;

/**
 * Helper service to ensure that data are exported properly.
 */
class JoinupSeoExportHelper implements JoinupSeoExportHelperInterface {

  /**
   * The SPARQL serializer service.
   *
   * @var \Drupal\sparql_entity_storage\SparqlSerializerInterface
   */
  protected $serializer;

  /**
   * Constructs a JoinupSeoExportHelper object.
   *
   * @param \Drupal\sparql_entity_storage\SparqlSerializerInterface $serializer
   *   The SPARQL serializer service.
   */
  public function __construct(SparqlSerializerInterface $serializer) {
    $this->serializer = $serializer;
  }

  /**
   * {@inheritdoc}
   */
  public function exportRdfEntityMetadata(RdfInterface $entity): string {
    if (!$output = $this->serializer->serializeEntity($entity, 'json')) {
      return '';
    }

    // Serializer returns an array reset() the results to display them properly.
    $output = json_decode($output);
    $output = (object) $output;
    return json_encode($output);
  }

}
