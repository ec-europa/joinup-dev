<?php

declare(strict_types = 1);

namespace Drupal\joinup_video\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for video entities in Joinup.
 */
interface VideoInterface extends NodeInterface, CollectionContentInterface {
}
