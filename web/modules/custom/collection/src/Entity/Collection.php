<?php

declare(strict_types = 1);

namespace Drupal\collection\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassMetaEntityTrait;
use Drupal\joinup_bundle_class\LogoTrait;
use Drupal\joinup_bundle_class\ShortIdTrait;
use Drupal\joinup_featured\FeaturedContentTrait;
use Drupal\joinup_group\Entity\GroupTrait;
use Drupal\joinup_workflow\ArchivableEntityTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Entity subclass for the 'collection' bundle.
 */
class Collection extends Rdf implements CollectionInterface {

  use ArchivableEntityTrait;
  use EntityWorkflowStateTrait;
  use FeaturedContentTrait;
  use GroupTrait;
  use JoinupBundleClassFieldAccessTrait;
  use JoinupBundleClassMetaEntityTrait;
  use LogoTrait;
  use ShortIdTrait;

  /**
   * {@inheritdoc}
   *
   * phpcs:disable Generic.CodeAnalysis.UselessOverridingMethod.Found
   */
  public static function create(array $values = []): CollectionInterface {
    // Delegate to the parent method. This is only overridden to provide the
    // correct return type.
    return parent::create($values);
  }

  /**
   * {@inheritdoc}
   */
  public function getSolutions(bool $published = TRUE): array {
    $ids = $this->getSolutionIds($published);
    if (empty($ids)) {
      return $ids;
    }
    return $this
      ->entityTypeManager()
      ->getStorage('rdf_entity')
      ->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function getSolutionIds(bool $published = TRUE): array {
    $ids = $this->getReferencedEntityIds('field_ar_affiliates')['rdf_entity'] ?? [];

    if ($ids && $published) {
      // Published solutions are stored in the 'default' graph.
      $ids = $this->entityTypeManager()
        ->getStorage('rdf_entity')
        ->getQuery()
        ->graphs(['default'])
        ->condition('rid', 'solution')
        ->condition('id', $ids, 'IN')
        ->execute();
      $ids = array_values($ids);
    }

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_ar_state';
  }

  /**
   * {@inheritdoc}
   */
  public function getLogoFieldName(): string {
    return 'field_ar_logo';
  }

}
