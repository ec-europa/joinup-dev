<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderInterface;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;

/**
 * RDF Entity dependency injection container.
 */
class SparqlEntityStorageServiceProvider implements ServiceProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function register(ContainerBuilder $container) {
    // Run this compiler pass after the child definitions were resolved.
    $container->addCompilerPass(new SparqlEncoderCompilerPass(), PassConfig::TYPE_OPTIMIZE, -10);
  }

}
