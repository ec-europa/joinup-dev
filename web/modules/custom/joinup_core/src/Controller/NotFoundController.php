<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Builds a custom 404 page with a simple text.
 */
class NotFoundController extends ControllerBase {

  /**
   * Constructs a 404 page.
   *
   * @return array
   *   The render array.
   */
  public function build404(): array {
    $search = Link::fromTextAndUrl('search function', Url::fromUri('internal:/search'));
    $front = Link::fromTextAndUrl('home page', Url::fromUri('internal:/<front>'));

    return [
      '#theme' => '404_not_found',
      '#search' => $search->toRenderable(),
      '#front' => $front->toRenderable(),
    ];
  }

}
