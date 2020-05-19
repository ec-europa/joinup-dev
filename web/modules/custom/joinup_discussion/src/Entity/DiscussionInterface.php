<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Entity;

use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for discussion entities in Joinup.
 */
interface DiscussionInterface extends NodeInterface, CommunityContentInterface {

}
