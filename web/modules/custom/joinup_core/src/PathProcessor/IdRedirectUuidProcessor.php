<?php

declare (strict_types = 1);

namespace Drupal\joinup_core\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Drupal\joinup_core\Controller\IdRedirect;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handles joinup_core.id_redirect route case when {uuid} param contains a '/'.
 *
 * Because the joinup_core.id_redirect route's {uuid} parameter may contain one
 * or more slashes, we encode the URL parameter because Drupal doesn't allow
 * slashes in parameters, as opposed to Symfony. We revert the variable in
 * IdRedirect::redirectToRdfEntity().
 *
 * @see https://www.drupal.org/project/drupal/issues/2741939
 * @see https://symfony.com/doc/3.4/routing/slash_in_parameter.html
 * @see \Drupal\joinup_core\Controller\IdRedirect::redirectToRdfEntity()
 */
class IdRedirectUuidProcessor implements InboundPathProcessorInterface {

  /**
   * {@inheritdoc}
   */
  public function processInbound($path, Request $request): string {
    if (strpos($path, '/data/') === 0 && !$request->query->has('uuid')) {
      preg_match('#/data/(?<namespace>[^/]+)/(?<uuid>.+)#', $path, $matches);
      if (!empty($matches['namespace']) && !empty($matches['uuid'][0]) && IdRedirect::getEntityTypeFromPersistentUriNamespace($matches['namespace'])) {
        return '/data/' . $matches['namespace'] . '/' . urlencode($matches['uuid']);
      }
    }
    return $path;
  }

}
