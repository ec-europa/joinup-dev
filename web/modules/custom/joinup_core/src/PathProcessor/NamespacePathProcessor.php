<?php

declare (strict_types = 1);

namespace Drupal\joinup_core\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Render\BubbleableMetadata;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to handle cases where the EC namespace ID contains a '/'.
 */
class NamespacePathProcessor implements InboundPathProcessorInterface, OutboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/data/') === 0 && !$request->query->has('uuid')) {
      preg_match_all('#/data/(?<namespace>[^/]+)/(?<uuid>.+)#', $path, $matches);
      if (!empty($matches)) {
        return '/data/' . $matches['namespace'] . '/' . urlencode($matches['uuid']);
      }
    }
    return $path;
  }

}
