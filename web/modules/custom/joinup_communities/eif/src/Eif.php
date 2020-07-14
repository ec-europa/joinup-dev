<?php

declare(strict_types = 1);

namespace Drupal\eif;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * A helper class for EIF.
 */
class Eif {

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
   * Constructs and Eif object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Checks if the user has access to the recommendations page.
   *
   * The user has access if the page is under the EIF Toolbox solution and the
   * user has view access for the solution.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(): AccessResultInterface {
    $group = $this->entityTypeManager->getStorage('rdf_entity')->load(self::EIF_ID);
    if (empty($group)) {
      return AccessResult::neutral();
    }

    return $this->entityTypeManager->getAccessControlHandler('rdf_entity')->access($group, 'view', NULL, TRUE);
  }

}
