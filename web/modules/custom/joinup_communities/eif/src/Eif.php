<?php

declare(strict_types = 1);

namespace Drupal\eif;

use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * A helper class for EIF.
 */
class Eif implements EifInterface {

  /**
   * The EIF toolbox solution ID.
   *
   * @var string
   */
  public const EIF_ID = 'http://data.europa.eu/w21/405d8980-3f06-4494-b34a-46c388a38651';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new service instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEifCategories(): array {
    $entity_storage = $this->entityTypeManager->getStorage('field_storage_config');
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $entity_storage->load('rdf_entity.field_is_eif_category');
    return $field_storage->getSetting('allowed_values') ?: [];
  }

}
