<?php

/**
 * @file
 * Contains \Drupal\joinup\Plugin\Block\AddContentBlock.
 */

namespace Drupal\joinup\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides an 'AddContentBlock' block.
 *
 * @Block(
 *  id = "add_content_block",
 *  admin_label = @Translation("Add content"),
 * )
 */
class AddContentBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [
      'collection' => [
        '#type' => 'link',
        '#title' => $this->t('Add collection'),
        '#url' => Url::fromRoute('collection.propose_form'),
        '#attributes' => ['class' => ['button', 'button--small']],
      ],
    ];

    return $build;
  }

}
