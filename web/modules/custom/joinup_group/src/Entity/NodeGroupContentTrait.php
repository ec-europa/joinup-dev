<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\rdf_entity\RdfInterface;

/**
 * Reusable methods for node group content.
 */
trait NodeGroupContentTrait {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup(): RdfInterface {
    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $audience_field */
    $audience_field = $this->getFirstItem('og_audience');
    if (!$audience_field || $audience_field->isEmpty()) {
      throw new MissingGroupException();
    }
    /** @var \Drupal\collection\Entity\CollectionInterface|\Drupal\solution\Entity\SolutionInterface $group */
    $group = $audience_field->entity;

    return $group;
  }

}
