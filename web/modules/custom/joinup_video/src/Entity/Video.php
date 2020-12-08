<?php

declare(strict_types = 1);

namespace Drupal\joinup_video\Entity;

use Drupal\collection\Entity\NodeCollectionContentTrait;
use Drupal\node\Entity\Node;

/**
 * Bundle class for nodes of type video.
 */
class Video extends Node implements VideoInterface {

  use NodeCollectionContentTrait;

}
