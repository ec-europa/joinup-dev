<?php

declare(strict_types = 1);

namespace Drupal\rdf_serialization;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Drupal\rdf_serialization\Encoder\RdfEncoder;

/**
 * Service provider for the rdf serialization module.
 */
class RdfSerializationServiceProvider implements ServiceProviderInterface {

  /**
   * Generate a encoder service for each RDF serialisation format.
   *
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    foreach (array_keys(RdfEncoder::supportedFormats()) as $format) {
      $service_name = 'rdf_serialization.encoder.' . $format;
      $container->register($service_name, 'Drupal\rdf_serialization\Encoder\RdfEncoder')
        ->addTag('encoder', ['format' => $format]);
    }
  }

}
