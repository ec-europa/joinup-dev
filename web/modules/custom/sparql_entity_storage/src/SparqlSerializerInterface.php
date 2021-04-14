<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Interface for classes that serialize SPARQL entities.
 */
interface SparqlSerializerInterface {

  /**
   * Exports a single entity to a serialised string.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to export.
   * @param string $format
   *   The serialisation format. Defaults to 'turtle'.
   * @param array $options
   *   An array of options to pass to the serializer. Please, see each
   *   serializer for the specific options. Serializers are under the namespace
   *   \EasyRdf\Serialiser.
   *
   * @return string
   *   The serialised entity as a string.
   */
  public function serializeEntity(ContentEntityInterface $entity, string $format = 'turtle', array $options = []): string;

}
