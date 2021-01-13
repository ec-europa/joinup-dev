<?php

declare(strict_types = 1);

namespace Drupal\contact_information;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\joinup_group\JoinupGroupHelper;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service to provide information about contact information entity relations.
 *
 * @todo Move this into the ContactInformation bundle class.
 * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6327
 */
class ContactInformationRelationInfo implements ContactInformationRelationInfoInterface, ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ContactInformationRelationInfo service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getContactInformationRelatedGroups(RdfInterface $entity): array {
    $query = $this->entityTypeManager->getStorage('rdf_entity')->getQuery();
    $condition_or = $query->orConditionGroup();
    // Contact entities are also referenced by releases but this value is
    // inherited by the solution directly so there is no need to check them.
    $condition_or->condition('field_ar_contact_information', $entity->id());
    $condition_or->condition('field_is_contact_information', $entity->id());
    $query->condition($condition_or);
    $query->condition('rid', JoinupGroupHelper::GROUP_BUNDLES, 'IN');
    $ids = $query->execute();

    return empty($ids) ? [] : $this->entityTypeManager->getStorage('rdf_entity')->loadMultiple($ids);
  }

}
