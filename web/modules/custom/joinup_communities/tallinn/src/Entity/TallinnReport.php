<?php

declare(strict_types = 1);

namespace Drupal\tallinn\Entity;

use Drupal\collection\Entity\NodeCommunityContentTrait;
use Drupal\node\Entity\Node;

/**
 * Bundle class for nodes of type Tallinn report.
 */
class TallinnReport extends Node implements TallinnReportInterface {

  use NodeCommunityContentTrait;

}
