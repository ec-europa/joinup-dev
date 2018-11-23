<?php

declare(strict_types = 1);

namespace Drupal\rdf_serialization;

use Drupal\rdf_serialization\Encoder\RdfEncoder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Implementation of "accept header"-based content negotiation.
 */
class AcceptHeaderMiddleware implements HttpKernelInterface {

  /**
   * Constructs a new AcceptHeaderMiddleware instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $app
   *   The app.
   */
  public function __construct(HttpKernelInterface $app) {
    $this->app = $app;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE) {
    if ($format = $this->determineFormat($request)) {
      $request->setRequestFormat($format);
    }
    return $this->app->handle($request, $type, $catch);
  }

  /**
   * Determines the output format (eg. html, json, rdfxml,..).
   *
   * @param $request
   *   The request object.
   *
   * @return null|string
   *   The format to use for this request.
   */
  protected function determineFormat($request):? string {
    // Use Accept header to determine format.
    $accept = $request->headers->get('Accept') ?: 'text/html';
    return $this->formatFromRdfMimeType($accept);
  }

  /**
   * Determines the format from the mime-type.
   *
   * @param string $mimeType
   *   The mime-type of the request.
   *
   * @return null|string
   *   Corresponding format.
   */
  protected function formatFromRdfMimeType(string $mimeType):? string {
    $formats = [];
    foreach (RdfEncoder::supportedFormats() as $format) {
      $formats[$format->getDefaultMimeType()] = $format->getName();
    }
    return $formats[$mimeType];
  }

}
