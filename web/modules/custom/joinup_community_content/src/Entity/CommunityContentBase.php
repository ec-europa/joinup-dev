<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\node\Entity\Node;

/**
 * Base class for community content entities.
 */
class CommunityContentBase extends Node implements CommunityContentInterface {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getCollection(): CollectionInterface {
    /** @var \Drupal\og\Plugin\Field\FieldType\OgStandardReferenceItem $audience_field */
    $audience_field = $this->getFirstItem('og_audience');
    if ($audience_field->isEmpty()) {
      throw new MissingCollectionException();
    }
    return $audience_field->entity;
  }

}
