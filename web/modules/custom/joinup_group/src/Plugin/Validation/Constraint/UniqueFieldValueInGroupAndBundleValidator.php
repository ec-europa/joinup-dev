<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\og\Og;
use Drupal\og\OgGroupAudienceHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the 'UniqueLabelInOgGroup' constraint.
 */
class UniqueFieldValueInGroupAndBundleValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity type bundle info service.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInfo;

  /**
   * Constructs a new constraint validator instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundle_info
   *   The entity type bundle info service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityTypeBundleInfoInterface $bundle_info) {
    $this->entityTypeManager = $entity_type_manager;
    $this->bundleInfo = $bundle_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint): void {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    if (!$item = $items->first()) {
      return;
    }

    /** @var \Drupal\joinup_group\Entity\GroupContentInterface $entity */
    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();

    // Ignore entities that are not group content.
    if (!Og::isGroupContent($entity_type_id, $bundle)) {
      return;
    }

    $group_audience_field = $constraint->groupAudienceField;
    if (!($group_audience_field_definition = $entity->getFieldDefinition($group_audience_field)) || !OgGroupAudienceHelper::isGroupAudienceField($group_audience_field_definition)) {
      throw new \InvalidArgumentException("Bundle '{$bundle}' of '{$entity_type_id}' entity type has no '{$group_audience_field}' group audience field.");
    }

    // Ignore when entity didn't set any group.
    if ($entity->get($group_audience_field)->isEmpty()) {
      return;
    }

    $field_definition = $items->getFieldDefinition();
    $field_name = $field_definition->getName();
    $entity_type = $entity->getEntityType();
    $id_key = $entity_type->getKey('id');
    $bundle_key = $entity_type->getKey('bundle') ?: $entity_type_id;

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $query = $storage->getQuery();

    $entity_id = $entity->id();
    // Using isset() instead of !empty() as 0 and '0' are valid ID values for
    // entity types using string IDs.
    if (isset($entity_id)) {
      $query->condition($id_key, $entity_id, '<>');
    }

    $value_taken = $query
      ->condition($group_audience_field, $entity->get($group_audience_field)->target_id)
      ->condition($field_name, $item->value)
      ->condition($bundle_key, $bundle)
      ->range(0, 1)
      ->execute();

    if ($value_taken) {
      $id = reset($value_taken);
      $other_entity = $storage->load($id);
      $bundle_info = $this->bundleInfo->getBundleInfo($entity_type_id)[$bundle];
      $this->context->addViolation($constraint->message, [
        '@bundle' => $bundle_info['label'],
        '@field_label' => mb_strtolower((string) $field_definition->getLabel()),
        '%value' => $item->value,
        ':url' => $other_entity->toUrl()->toString(),
        '@label' => $other_entity->label(),
      ]);
    }
  }

}
