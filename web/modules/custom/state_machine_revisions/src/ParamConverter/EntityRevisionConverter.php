<?php

namespace Drupal\state_machine_revisions\ParamConverter;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\ParamConverter\EntityConverter;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\state_machine_revisions\RevisionManagerInterface;
use Symfony\Component\Routing\Route;

/**
 * Defines a class for making sure the edit-route loads the current draft.
 */
class EntityRevisionConverter extends EntityConverter {

  /**
   * The revision manager.
   *
   * @var \Drupal\state_machine_revisions\RevisionManagerInterface
   */
  protected $revisionManager;

  /**
   * Constructs a new EntityRevisionConverter object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\state_machine_revisions\RevisionManagerInterface $revision_manager
   *   The revision manager.
   */
  public function __construct(EntityManagerInterface $entity_manager, RevisionManagerInterface $revision_manager) {
    parent::__construct($entity_manager);

    $this->revisionManager = $revision_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return $this->isEditFormPage($route);
  }

  /**
   * Determines if a given route is the edit-form for an entity.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route definition.
   *
   * @return bool
   *   Returns TRUE if the route is the edit form of an entity, FALSE otherwise.
   *
   * @see \Drupal\content_moderation\ParamConverter\EntityRevisionConverter::isEditFormPage()
   */
  protected function isEditFormPage(Route $route) {
    if ($default = $route->getDefault('_entity_form')) {
      // If no operation is provided, use 'default'.
      $default .= '.default';
      list($entity_type_id, $operation) = explode('.', $default);
      if (!$this->entityManager->hasDefinition($entity_type_id)) {
        return FALSE;
      }
      $entity_type = $this->entityManager->getDefinition($entity_type_id);
      return $operation == 'edit' && $entity_type && $entity_type->isRevisionable();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    $entity = parent::convert($value, $definition, $name, $defaults);

    if ($entity && $entity->getEntityType()->isRevisionable() && !$this->revisionManager->isLatestRevision($entity)) {
      $latest_revision = $this->revisionManager->loadLatestRevision($entity);

      // If the entity type is translatable, ensure we return the proper
      // translation object for the current context.
      // @see parent::convert()
      if ($latest_revision instanceof EntityInterface && $entity instanceof TranslatableInterface) {
        $latest_revision = $this->entityManager->getTranslationFromContext($latest_revision, NULL, array('operation' => 'entity_upcast'));
      }

      if ($latest_revision->isRevisionTranslationAffected()) {
        $entity = $latest_revision;
      }
    }

    return $entity;
  }

}
