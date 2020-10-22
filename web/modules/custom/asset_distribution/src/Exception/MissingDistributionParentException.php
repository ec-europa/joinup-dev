<?php

declare(strict_types = 1);

namespace Drupal\asset_distribution\Exception;

/**
 * Exception thrown when a distribution mises the a parent entity.
 *
 * @see \Drupal\asset_distribution\Entity\AssetDistributionInterface::getParent()
 */
class MissingDistributionParentException extends \Exception {

}
