<?php

declare(strict_types = 1);

namespace Drupal\easme_helper\Plugin\rest\resource;

use Drupal\Component\Serialization\Json;
use Drupal\easme_helper\Controller\SubRequestController;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to update a user through JSON:API with a POST method.
 *
 * @RestResource(
 *   id = "easme_user_update_rest_resource",
 *   label = @Translation("EASME user update"),
 *   uri_paths = {
 *     "create" = "/easme_rest_api/user/user/{uuid}"
 *   }
 * )
 */
class EasmeUserUpdate extends ResourceBase {

  /**
   * Jsonapi.base_path config property.
   *
   * @var string
   */
  protected $jsonApiBasePath;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->jsonApiBasePath = $container->getParameter('jsonapi.base_path');
    return $instance;
  }

  /**
   * Responds to POST requests.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param string $uuid
   *   The UUID.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The response object.
   */
  public function post(Request $request, string $uuid) {
    // Process the JSON:API sub-request.
    // Prepare the request.
    $httpKernel = \Drupal::service('http_kernel.basic');
    $requestStack = \Drupal::requestStack();
    $sub_request = new SubRequestController($httpKernel, $requestStack);

    // Keep the intial request's headers.
    $headers = $request->headers->all();
    // Add required headers for JSON:API requests.
    $headers['accept'] = ['application/vnd.api+json'];
    $headers['content-type'] = ['application/vnd.api+json'];

    $content = $request->getContent();
    $uri = $this->jsonApiBasePath . '/user/user/' . $uuid;
    $response = $sub_request->subRequest($uri, 'PATCH', [], $content, $headers);
    return new ResourceResponse(Json::decode($response));
  }

}
