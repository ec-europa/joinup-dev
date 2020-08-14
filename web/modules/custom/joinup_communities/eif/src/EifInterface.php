<?php

declare(strict_types = 1);

namespace Drupal\eif;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Provides an interface for EIF Toolbox helper class.
 */
interface EifInterface {

  /**
   * The EIF toolbox solution ID.
   *
   * @var string
   */
  public const EIF_ID = 'http://data.europa.eu/w21/405d8980-3f06-4494-b34a-46c388a38651';

  /**
   * The EIF Toolbox categories.
   *
   * @var string[]
   */
  public const EIF_CATEGORIES = [
    'assessment-tools' => 'Assessment tools',
    'common-frameworks' => 'Common frameworks',
    'common-services' => 'Common services',
    'generic-tools' => 'Generic tools',
    'legal-interoperability-tools' => 'Legal interoperability tools',
    'semantic-assets' => 'Semantic assets',
  ];

  /**
   * The node ID of the EIF Toolbox solutions custom page.
   *
   * @var int
   */
  public const EIF_SOLUTIONS_NID = 703013;

  /**
   * Returns the EIF categories.
   *
   * This is a wrapper around static::EIF_ID, used in the
   * 'rdf_entity.field_is_eif_category' field storage definition.
   *
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $definition
   *   The field storage definition.
   * @param \Drupal\Core\Entity\FieldableEntityInterface|null $entity
   *   (optional) The entity context if known, or NULL if the allowed values are
   *   being collected without the context of a specific entity.
   * @param bool &$cacheable
   *   (optional) If an $entity is provided, the $cacheable parameter should be
   *   modified by reference and set to FALSE if the set of allowed values
   *   returned was specifically adjusted for that entity and cannot not be
   *   reused for other entities. Defaults to TRUE.
   *
   * @return array
   *   A list of category labels keyed by category value.
   *
   * @see config/sync/field.storage.rdf_entity.field_is_eif_category.yml
   * @see callback_allowed_values_function()
   */
  public static function getCategories(FieldStorageDefinitionInterface $definition, ?FieldableEntityInterface $entity = NULL, bool &$cacheable = TRUE): array;

}
