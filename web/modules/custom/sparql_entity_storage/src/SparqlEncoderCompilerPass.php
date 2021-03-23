<?php

declare(strict_types = 1);

namespace Drupal\sparql_entity_storage;

use EasyRdf\Format;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Collects all RDF encoders and stores them into a service container parameter.
 */
class SparqlEncoderCompilerPass implements CompilerPassInterface {

  /**
   * {@inheritdoc}
   */
  public function process(ContainerBuilder $container): void {
    $rdf_formats = array_keys(Format::getFormats());
    $encoders = [];
    foreach ($container->findTaggedServiceIds('encoder') as $id => $attributes) {
      $class = $container->getDefinition($id)->getClass();
      $interfaces = class_implements($class);
      $format = $attributes[0]['format'];
      if (isset($interfaces[SparqlEncoderInterface::class]) && in_array($format, $rdf_formats)) {
        $encoders[$format] = $format;
      }
    }
    $container->setParameter('sparql_entity.encoders', $encoders);
  }

}
