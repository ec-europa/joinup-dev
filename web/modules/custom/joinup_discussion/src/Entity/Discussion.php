<?php

declare(strict_types = 1);

namespace Drupal\joinup_discussion\Entity;

use Drupal\joinup_community_content\Entity\CommunityContentBase;
use Drupal\joinup_workflow\ArchivableEntityTrait;

/**
 * Entity subclass for the 'discussion' bundle.
 */
class Discussion extends CommunityContentBase implements DiscussionInterface {

  use ArchivableEntityTrait;

}
