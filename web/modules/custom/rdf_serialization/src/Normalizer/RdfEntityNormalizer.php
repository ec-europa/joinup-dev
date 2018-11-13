<?php

namespace Drupal\rdf_serialization\Normalizer;

use Drupal\rdf_export\RdfSerializer;
use Drupal\serialization\Normalizer\FieldableEntityNormalizerTrait;

/**
 * Converts the Drupal entity object structure to a HAL array structure.
 */
class RdfEntityNormalizer extends NormalizerBase {

  use FieldableEntityNormalizerTrait;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\rdf_entity\RdfInterface';

  /**
   * RdfEntityNormalizer constructor.
   *
   * @param \Drupal\rdf_export\RdfSerializer $rdfSerializer
   *   RDF Serializer service.
   */
  public function __construct(RdfSerializer $rdfSerializer) {
    $this->rdfSerializer = $rdfSerializer;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    return ['_rdf_entity' => $this->rdfSerializer->serializeEntity($entity, $format)];
  }

  /**
   * Implements \Symfony\Component\Serializer\Normalizer\DenormalizerInterface::denormalize().
   *
   * @param array $data
   *   Entity data to restore.
   * @param string $class
   *   Unused, entity_create() is used to instantiate entity objects.
   * @param string $format
   *   Format the given data was extracted from.
   * @param array $context
   *   Options available to the denormalizer. Keys that can be used:
   *   - request_method: if set to "patch" the denormalization will clear out
   *     all default values for entity fields before applying $data to the
   *     entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An unserialized entity object containing the data in $data.
   *
   * @throws \Symfony\Component\Serializer\Exception\UnexpectedValueException
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    return NULL;
  }

}
