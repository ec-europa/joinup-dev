<?php

declare(strict_types = 1);

namespace Drupal\joinup_sparql;

use Drupal\sparql_entity_storage\Encoder\SparqlEncoder;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Implementation of 'Accept' header based content negotiation.
 */
class JoinupAcceptHeaderMiddleware implements HttpKernelInterface {

  /**
   * The decorated kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * Constructs a new AcceptHeaderMiddleware instance.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $http_kernel
   *   The decorated kernel.
   */
  public function __construct(HttpKernelInterface $http_kernel) {
    $this->httpKernel = $http_kernel;
  }

  /**
   * {@inheritdoc}
   */
  public function handle(Request $request, $type = self::MASTER_REQUEST, $catch = TRUE): Response {
    if ($format = $this->getFormatFromRequest($request)) {
      $request->setRequestFormat($format);
    }
    return $this->httpKernel->handle($request, $type, $catch);
  }

  /**
   * Determines the output format (eg. html, json, rdfxml,..).
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return string|null
   *   The format to use for this request. NULL will be returned for all non-RDF
   *   formats.
   */
  protected function getFormatFromRequest(Request $request): ?string {
    $accept_header_string = $request->headers->get('Accept', NULL, TRUE) ?: 'text/html';
    // Use the 'Accept' header to determine format.
    $accept_header = AcceptHeader::fromString($accept_header_string);
    // AcceptHeader::first() is already sorting the list. The first is the
    // client most wanted mime.
    // @see \Symfony\Component\HttpFoundation\AcceptHeader::first()
    $mime_type = $accept_header->first()->getValue();

    /** @var \EasyRdf\Format $format */
    foreach (SparqlEncoder::getSupportedFormats() as $format) {
      if ($format->getDefaultMimeType() === $mime_type) {
        return $format->getName();
      }
    }

    // Return the format only for RDF formats. NULL means pass-through.
    return NULL;
  }

}
