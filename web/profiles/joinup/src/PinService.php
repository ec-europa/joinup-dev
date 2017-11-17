<?php

namespace Drupal\joinup;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\rdf_entity\RdfInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A service to handle pinned entities.
 */
class PinService implements PinServiceInterface, ContainerInjectionInterface {

  /**
   * The field that holds the collections where a solution is pinned in.
   *
   * @var string
   */
  const SOLUTION_PIN_FIELD = 'field_is_pinned_in';

  /**
   * The relations manager service.
   *
   * @var \Drupal\joinup_core\JoinupRelationManagerInterface
   */
  protected $relationManager;

  /**
   * Constructs a PinService service.
   *
   * @param \Drupal\joinup_core\JoinupRelationManagerInterface $relationManager
   *   The relations manager service.
   */
  public function __construct(JoinupRelationManagerInterface $relationManager) {
    $this->relationManager = $relationManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('joinup_core.relations_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isEntityPinned(ContentEntityInterface $entity, RdfInterface $collection = NULL) {
    if (JoinupHelper::isSolution($entity)) {
      if (empty($collection)) {
        return !$entity->get(self::SOLUTION_PIN_FIELD)->isEmpty();
      }
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      foreach ($entity->get(self::SOLUTION_PIN_FIELD)->referencedEntities() as $rdf) {
        if ($rdf->id() === $collection->id()) {
          return TRUE;
        }
      }
    }
    elseif (JoinupHelper::isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the sticky status.
      /** @var \Drupal\node\NodeInterface $entity */
      return $entity->isSticky();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityPinned(ContentEntityInterface $entity, RdfInterface $collection, bool $pinned) {
    if (JoinupHelper::isSolution($entity)) {
      $field = $entity->get(self::SOLUTION_PIN_FIELD);
      if ($pinned) {
        $field->appendItem($collection->id());
      }
      else {
        $field->filter(function ($item) use ($collection) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
          return $item->target_id !== $collection->id();
        });
      }
    }
    elseif (JoinupHelper::isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the sticky status.
      /** @var \Drupal\node\NodeInterface $entity */
      $entity->setSticky($pinned);
    }

    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionsWherePinned(ContentEntityInterface $entity) {
    if (JoinupHelper::isSolution($entity)) {
      return $entity->get(self::SOLUTION_PIN_FIELD)->referencedEntities();
    }
    elseif (JoinupHelper::isCommunityContent($entity) && $entity->isSticky()) {
      return [$this->relationManager->getParent($entity)];
    }

    return [];
  }

}
