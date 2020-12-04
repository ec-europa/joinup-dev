<?php

namespace Drupal\easme_helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class SubRequestController.
 *
 * @package Drupal\easme_helper\Controller
 */
class SubRequestController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * Symfony\Component\HttpKernel\HttpKernelInterface definition.
   *
   * @var Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $httpKernel;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  public function __construct(HttpKernelInterface $http_kernel, RequestStack $request_stack) {
    $this->httpKernel = $http_kernel;
    $this->requestStack = $request_stack;
  }

  /**
   * Performs a subrequest.
   *
   * @param string $path
   *   Path to use for subrequest.
   * @param string $method
   *   The HTTP method to use, eg. Get, Post.
   * @param array $parameters
   *   The query parameters.
   * @param string|resource|null $content
   *   The raw body data.
   * @param array $headers
   *   Additional headers to use in the request.
   *
   * @return string
   *   The response String.
   *
   * @throws \Exception
   */
  public function subRequest($path, $method = 'GET', array $parameters = [], $content = NULL, $headers = []) {
    $sub_request = Request::create($path, $method, $parameters, $cookies = [], $files = [], $server = [], $content);

    // Set headers if any.
    if (!empty($headers)) {
      foreach ($headers as $key => $value) {
        $sub_request->headers->set($key, $value);
      }
    }

    $sub_request->setSession($this->requestStack->getCurrentRequest()->getSession());

    $subResponse = $this->httpKernel->handle($sub_request, HttpKernelInterface::SUB_REQUEST, FALSE);

    return $subResponse->getContent();
  }

}
