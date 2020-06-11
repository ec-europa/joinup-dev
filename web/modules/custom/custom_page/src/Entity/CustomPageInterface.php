<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for content page node entities.
 */
interface CustomPageInterface extends NodeInterface, GroupContentInterface, CollectionContentInterface {
}
