<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope;
use Drupal\joinup_featured\FeaturedContentInterface;
use Drupal\node\Entity\Node;

/**
 * Behat step definitions to interact with content that is featured site-wide.
 */
class JoinupFeaturedContext extends RawDrupalContext {

  /**
   * Features newly created nodes.
   *
   * This checks if the "Featured" property is set for a newly created node, and
   * sets the status accordingly. This is not done as part of regular node
   * creation since this data is not part of the node but is stored in a
   * metadata entity.
   *
   * @param \Drupal\DrupalExtension\Hook\Scope\AfterNodeCreateScope $scope
   *   The Behat hook scope object containing the metadata of the node that was
   *   created.
   *
   * @AfterNodeCreate
   */
  public function featureNodeAfterCreation(AfterNodeCreateScope $scope) {
    $node = $scope->getEntity();

    $is_featured = in_array(strtolower((string) ($node->featured ?? '')), [
      'y',
      'yes',
    ]);
    $nid = $node->nid ?? NULL;
    if ($is_featured && $nid) {
      /** @var \Drupal\node\NodeInterface $entity */
      if ($entity = Node::load((int) $nid)) {
        if ($entity instanceof FeaturedContentInterface) {
          $entity->feature();
        }
      }
    }
  }

}
