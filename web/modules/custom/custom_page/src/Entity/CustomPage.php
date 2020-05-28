<?php

declare(strict_types = 1);

namespace Drupal\custom_page\Entity;

use Drupal\collection\Entity\NodeCollectionContentTrait;
use Drupal\node\Entity\Node;

/**
 * Entity class for custom page node entities.
 */
class CustomPage extends Node implements CustomPageInterface {

  use NodeCollectionContentTrait;

}
