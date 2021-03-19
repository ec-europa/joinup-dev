<?php

declare (strict_types = 1);

namespace Drupal\joinup_core\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\joinup_core\Controller\IdRedirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * Path processor to handle cases where the EC namespace ID contains a '/'.
 */
class NamespacePathProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\joinup_core\Controller\IdRedirect::redirectToRdfEntity
   */
  public function processInbound($path, Request $request) {
    if (strpos($path, '/data/') === 0 && !$request->query->has('uuid')) {
      preg_match_all('#/data/(?<namespace>[^/]+)/(?<uuid>.+)#', $path, $matches);
      if (!empty($matches['namespace'][0]) && !empty($matches['uuid'][0]) && IdRedirect::getEntityTypeFromPersistentUriNamespace($matches['namespace'][0])) {
        return '/data/' . $matches['namespace'][0] . '/' . urlencode($matches['uuid'][0]);
      }
    }
    return $path;
  }

}
