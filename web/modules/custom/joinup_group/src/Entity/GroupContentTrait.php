<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\Entity;

use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_group\Exception\MissingGroupException;
use Drupal\og\OgGroupAudienceHelperInterface;

/**
 * Reusable methods for node group content.
 */
trait GroupContentTrait {

  use JoinupBundleClassFieldAccessTrait;

  /**
   * {@inheritdoc}
   */
  public function getGroup(): GroupInterface {
    $group = $this->getFirstReferencedEntity(OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    if (empty($group) || !$group instanceof GroupInterface) {
      throw new MissingGroupException();
    }

    return $group;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupId(): string {
    $ids = $this->getReferencedEntityIds(OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    if (empty($ids['rdf_entity'])) {
      throw new MissingGroupException();
    }
    return array_shift($ids['rdf_entity']);
  }

}
