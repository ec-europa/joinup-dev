<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence;

use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\joinup_licence\Entity\CompatibilityDocumentInterface;

/**
 * Custom rendering for compatibility documents.
 */
class CompatibilityDocumentViewBuilder extends EntityViewBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode) {
    if (empty($entities)) {
      return;
    }

    parent::buildComponents($build, $entities, $displays, $view_mode);

    foreach ($entities as $id => $entity) {
      if (!$entity instanceof CompatibilityDocumentInterface) {
        continue;
      }

      $bundle = $entity->bundle();
      $display = $displays[$bundle];

      if ($display->getComponent('description')) {
        $build[$id]['description'][0]['#text'] = $entity->getDescription();
      }
    }
  }

}
