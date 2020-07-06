<?php

declare(strict_types = 1);

namespace Drupal\joinup_community_content\Entity;

use Drupal\collection\Entity\NodeCollectionContentTrait;
use Drupal\joinup_workflow\EntityWorkflowStateTrait;
use Drupal\node\Entity\Node;

/**
 * Base class for community content entities.
 */
class CommunityContentBase extends Node implements CommunityContentInterface {

  use EntityWorkflowStateTrait;
  use NodeCollectionContentTrait;

  /**
   * {@inheritdoc}
   */
  public function getWorkflowStateFieldName(): string {
    return 'field_state';
  }

}
