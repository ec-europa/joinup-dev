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
    $output = $this->serializer->serializeEntity($entity, 'jsonld', $this->getOptions());
    // The serializer returns also data where the entity is referred, thus, not
    // related to the metadata. Keep only the entry that has a "@type" property.
    $output = json_decode($output);
    foreach ($output->{"@graph"} as $object) {
      if (isset($object->{"@type"})) {
        $output->{"@graph"} = [$object];
        return json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
      }
    }
    return '';
  }

  /**
   * Returns a static list of options for the serializer.
   *
   * @return array
   *   An array of options for the JSON-LD serializer.
   */
  protected function getOptions(): array {
    // The \EasyRdf\Serialiser\JsonLd uses the \ML\JsonLD class to generate the
    // final output. The \ML\JsonLD has two ways of using the context. Either
    // downloading it from a web source or by passing an object. For the web
    // resource, the expected response has to be in the 'application/jsonld'
    // format, something that we do not have. However, even for performance and
    // availability purposes, a local file is used instead.
    $fixtures_path = __DIR__ . '/../fixtures/adms-ap-2.01.jsonld';
    $content = file_get_contents($fixtures_path);
    $content = json_decode($content);
    return [
      // Frame contains the "@context" and passes this to the values.
      'frame' => $content,
    ];
  }

}
