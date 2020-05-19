<?php

declare(strict_types = 1);

namespace Drupal\joinup_document\Entity;

use Drupal\joinup_community_content\Entity\CommunityContentInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for document entities in Joinup.
 */
interface DocumentInterface extends NodeInterface, CommunityContentInterface {

}
