<?php

declare(strict_types = 1);

namespace Drupal\eira\Controller;

use Drupal\Core\Cache\CacheableResponse;
use Drupal\Core\Controller\ControllerBase;
use EasyRdf\Format;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Provides a controller for 'eira.vocabulary' route.
 */
class Eira extends ControllerBase {

  /**
   * Returns the EIRA vocabulary as RDF/XML.
   *
   * The response is cached permanently as data comes from a static file which
   * is part of the codebase. If the source file is changed, that means a new
   * release will be created. Deploying a new release, *always* triggers an
   * explicit cache clear.
   *
   * @return \Drupal\Core\Cache\CacheableResponse
   *   The response as RDF/XML format.
   */
  public function vocabulary(): CacheableResponse {
    $response = new CacheableResponse();
    $response->setContent(file_get_contents(DRUPAL_ROOT . '/../resources/fixtures/EIRA_SKOS.rdf'));

    /** @var \EasyRdf\Format $format */
    $format = Format::getFormats()['rdfxml'];
    $response->headers->set('Content-Type', $format->getDefaultMimeType());

    // Attempt to render the result in browser, if the browser accepts.
    $disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, 'eira.' . $format->getDefaultExtension());
    $response->headers->set('Content-Disposition', $disposition);

    return $response;
  }

}
