<?php

declare(strict_types = 1);

namespace Drupal\joinup_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\rdf_entity\Entity\Rdf;
use Drupal\rdf_entity\RdfEntitySparqlStorageInterface;
use Drupal\rdf_entity\RdfInterface;
use Psr\Log\LoggerInterface;

/**
 * Helper methods for working with groups (collections and solutions).
 */
class GroupHelper implements GroupHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * A logger instance.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs a new GroupHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerInterface $logger) {
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function getGroupByLabel(string $label): RdfInterface {
    $query = $this->getRdfEntityStorage()->getQuery()
      ->condition('label', $label)
      ->condition('rid', self::BUNDLES, 'IN');
    $result = $query->execute();

    if (empty($result)) {
      throw new \InvalidArgumentException("No group found with label '$label'.");
    }

    if (count($result) > 1) {
      $this->logger->warning("Found multiple groups with the label '$label'.");
    }

    return Rdf::load(reset($result));
  }

  /**
   * The storage handler for RDF Entities.
   *
   * @return \Drupal\rdf_entity\RdfEntitySparqlStorageInterface
   *   The storage handler.
   */
  protected function getRdfEntityStorage(): RdfEntitySparqlStorageInterface {
    try {
      return $this->entityTypeManager->getStorage('rdf_entity');
    }
    catch (\Drupal\Component\Plugin\Exception\PluginNotFoundException $e) {
      // Shouldn't happen because we depend on the RDF Entity module which
      // defines the RDF storage.
      throw new \RuntimeException('RDF Entity storage is not defined.', 0, $e);
    }
    catch (\Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException $e) {
      // Shouldn't happen because we depend on the RDF Entity module which
      // defines the RDF storage.
      throw new \RuntimeException('RDF Entity storage is not valid.', 0, $e);
    }
  }

}
