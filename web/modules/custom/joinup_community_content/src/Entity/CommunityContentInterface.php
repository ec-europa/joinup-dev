<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\joinup_group\Entity\PinnableGroupContentInterface;
use Drupal\joinup_publication_date\Entity\EntityPublicationDateInterface;
use Drupal\joinup_stats\Entity\VisitCountAwareInterface;
use Drupal\joinup_workflow\EntityWorkflowStateInterface;
use Drupal\node\NodeInterface;

/**
 * Interface for community content entities.
 */
interface CommunityContentInterface extends NodeInterface, EntityPublicationDateInterface, FeaturedContentInterface, PinnableGroupContentInterface, CollectionContentInterface, EntityWorkflowStateInterface, VisitCountAwareInterface {

}
