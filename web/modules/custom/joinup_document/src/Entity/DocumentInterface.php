<?php

declare(strict_types = 1);

namespace Drupal\joinup_document\Entity;

use Drupal\joinup_bundle_class\LogoInterface;
use Drupal\joinup_community_content\Entity\CommunityContentInterface;

/**
 * Interface for document entities in Joinup.
 */
interface DocumentInterface extends CommunityContentInterface, LogoInterface {

}
