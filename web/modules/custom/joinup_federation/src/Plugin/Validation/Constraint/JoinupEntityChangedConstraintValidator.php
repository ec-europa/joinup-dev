<?php

declare(strict_types = 1);

namespace Drupal\joinup_federation\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Plugin\Validation\Constraint\EntityChangedConstraintValidator;
use Drupal\joinup_federation\StagingCandidateGraphsInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;

/**
 * Replaces the EntityChangedConstraintValidator validator.
 */
class JoinupEntityChangedConstraintValidator extends EntityChangedConstraintValidator implements ContainerInjectionInterface {

  use JoinupEntityReferenceConstraintTrait;

  /**
   * Creates a new plugin instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\joinup_federation\StagingCandidateGraphsInterface $staging_candidate_graphs
   *   The staging candidate graphs service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, StagingCandidateGraphsInterface $staging_candidate_graphs) {
    $this->entityTypeManager = $entity_type_manager;
    $this->stagingCandidateGraphs = $staging_candidate_graphs;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('joinup_federation.staging_candidate_graphs')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($entity, Constraint $constraint) {
    if (isset($entity)) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      if (!$entity->isNew()) {
        $saved_entity = $this->loadUnchanged($entity);
        // Ensure that all the entity translations are the same as or newer
        // than their current version in the storage in order to avoid
        // reverting other changes. In fact the entity object that is being
        // saved might contain an older entity translation when different
        // translations are being concurrently edited.
        if ($saved_entity) {
          $common_translation_languages = array_intersect_key($entity->getTranslationLanguages(), $saved_entity->getTranslationLanguages());
          foreach (array_keys($common_translation_languages) as $langcode) {
            // Merely comparing the latest changed timestamps across all
            // translations is not sufficient since other translations may have
            // been edited and saved in the meanwhile. Therefore, compare the
            // changed timestamps of each entity translation individually.
            if ($saved_entity->getTranslation($langcode)->getChangedTime() > $entity->getTranslation($langcode)->getChangedTime()) {
              $this->context->addViolation($constraint->message);
              break;
            }
          }
        }
      }
    }
  }

}
