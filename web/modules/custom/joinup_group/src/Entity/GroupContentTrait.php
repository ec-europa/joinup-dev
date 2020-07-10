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
    /** @var \Drupal\joinup_group\Entity\GroupInterface[] $groups */
    $groups = $this->getReferencedEntities(OgGroupAudienceHelperInterface::DEFAULT_FIELD);
    if (empty($groups)) {
      throw new MissingGroupException();
    }

    return reset($groups);
  }

}
