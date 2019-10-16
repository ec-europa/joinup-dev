<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a path processor to rewrite the licence comparer URL.
 *
 * As the route system does not allow arbitrary amount of parameters convert
 * the file path to a query parameter on the request.
 */
class SpdxIdsPathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {
    if (strpos($path, '/licence/compare/') === 0 && !$request->query->has('spdx_ids')) {
      $spdx_ids_flat = substr($path, 17);
      $spdx_ids = explode('/', trim($spdx_ids_flat, '/'));
      $request->query->set('spdx_ids', $spdx_ids);
      return '/licence/compare';
    }
    return $path;
  }

  /**
   * {@inheritdoc}
   */
  public function processOutbound($path, &$options = [], Request $request = NULL, BubbleableMetadata $bubbleable_metadata = NULL): string {
    if ($path === '/licence/compare' && $request->query->has('spdx_ids')) {
      $ids_path_part = implode('/', $request->query->get('spdx_ids'));
      return "{$path}/{$ids_path_part}";
    }
    return $path;
  }

}
