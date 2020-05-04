<?php

declare(strict_types = 1);

namespace Drupal\joinup_news\Entity;

use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for news entities in Joinup.
 */
interface NewsInterface extends NodeInterface, CommunityContentInterface {

}
