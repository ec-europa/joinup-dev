<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Entity;

use Drupal\collection\Entity\CommunitiesContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for Tallinn report entities.
 */
interface TallinnReportInterface extends NodeInterface, CommunitiesContentInterface {
}
