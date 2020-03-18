<?php

declare(strict_types=1);

namespace Drupal\joinup;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_community_content\CommunityContentHelper;
use Drupal\joinup_core\JoinupRelationManagerInterface;
use Drupal\joinup_group\JoinupGroupHelper;
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
  public function isEntityPinned(ContentEntityInterface $entity, RdfInterface $group = NULL) {
    if (JoinupGroupHelper::isSolution($entity)) {
      if (empty($group)) {
        return !$entity->get(self::SOLUTION_PIN_FIELD)->isEmpty();
      }
      /** @var \Drupal\rdf_entity\RdfInterface $entity */
      foreach ($entity->get(self::SOLUTION_PIN_FIELD)->referencedEntities() as $rdf) {
        if ($rdf->id() === $group->id()) {
          return TRUE;
        }
      }
    }
    elseif (CommunityContentHelper::isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the pinned status.
      /** @var \Drupal\node\NodeInterface $entity */
      return $entity->isSticky();
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityPinned(ContentEntityInterface $entity, RdfInterface $group, bool $pinned) {
    if (JoinupGroupHelper::isSolution($entity)) {
      $field = $entity->get(self::SOLUTION_PIN_FIELD);
      if ($pinned) {
        $field->appendItem($group->id());
      }
      else {
        $field->filter(function ($item) use ($group) {
          /** @var \Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem $item */
          return $item->target_id !== $group->id();
        });
      }
    }
    elseif (CommunityContentHelper::isCommunityContent($entity)) {
      // Nodes have only one possible parent, so the sticky boolean field
      // reflects the pinned status.
      /** @var \Drupal\node\NodeInterface $entity */
      $entity->setSticky($pinned);
    }

    $entity->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupsWherePinned(ContentEntityInterface $entity) {
    if (JoinupGroupHelper::isSolution($entity)) {
      return $entity->get(self::SOLUTION_PIN_FIELD)->referencedEntities();
    }
    elseif (CommunityContentHelper::isCommunityContent($entity) && $entity->isSticky()) {
      return [$this->relationManager->getParent($entity)];
    }

    return [];
  }

}
