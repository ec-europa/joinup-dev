<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_core\Entity\OutdatedContentInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_front_page\Entity\PinnableToFrontpageInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationTimeInterface;
use Drupal\joinup_stats\Entity\VisitCountAwareInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for community content entities.
 */
interface CommunityContentInterface extends NodeInterface, EntityPublicationTimeInterface, FeaturedContentInterface, PinnableGroupContentInterface, PinnableToFrontpageInterface, CollectionContentInterface, EntityWorkflowStateInterface, OutdatedContentInterface, VisitCountAwareInterface {

}
