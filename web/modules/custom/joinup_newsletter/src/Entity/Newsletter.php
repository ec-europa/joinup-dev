<?php

declare(strict_types = 1);

namespace Drupal\joinup_newsletter\Entity;

use Drupal\collection\Entity\NodeCollectionContentTrait;
use Drupal\node\Entity\Node;

/**
 * Bundle class for nodes of type newsletter.
 */
class Newsletter extends Node implements NewsletterInterface {

  use NodeCollectionContentTrait;

}
