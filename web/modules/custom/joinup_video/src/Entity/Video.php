<?php

declare(strict_types = 1);

namespace Drupal\joinup_video\Entity;

use Drupal\collection\Entity\NodeCommunityContentTrait;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeTrait;
use Drupal\node\Entity\Node;

/**
 * Bundle class for nodes of type video.
 */
class Video extends Node implements VideoInterface {

  use EntityPublicationTimeTrait;
  use NodeCommunityContentTrait;

}
