<?php

declare(strict_types = 1);

namespace Drupal\rdf_entity\Plugin\pathauto\AliasType;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\pathauto\PathautoState;
use Drupal\pathauto\Plugin\pathauto\AliasType\EntityAliasTypeBase;
use Drupal\sparql_entity_storage\Entity\Query\Sparql\Query;
use Drupal\sparql_entity_storage\UriEncoder;

/**
 * A pathauto alias type plugin for RDF entities.
 *
 * @AliasType(
 *   id = "rdf_entity",
 *   label = @Translation("Rdf entity"),
 *   types = {"rdf_entity"},
 *   provider = "rdf_entity",
 *   context_definitions = {
 *     "rdf_entity" = @ContextDefinition("entity:rdf_entity"),
 *   },
 * )
 */
class RdfEntityAliasType extends EntityAliasTypeBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeId() {
    return 'rdf_entity';
  }

  /**
   * {@inheritdoc}
   */
  public function getSourcePrefix() {
    return '/rdf_entity/';
  }

  /**
   * {@inheritdoc}
   */
  public function batchUpdate($action, &$context) {
    if (!isset($context['sandbox']['count'])) {
      $context['sandbox']['count'] = 0;
      $query = $this->getRdfEntityQuery();
      $query->addTag('rdf_entity_pathauto_bulk_update');
      $context['sandbox']['rdf_entity_ids'] = $query->execute();
      $context['sandbox']['updates'] = 0;

      switch ($action) {
        case 'create':
          // Process RDF entities that are not in the list of URL aliases.
          $aliased_rdf_entity_ids = $this->getAliasedEntityIds($context['sandbox']);
          $context['sandbox']['ids_to_process'] = array_diff($context['sandbox']['rdf_entity_ids'], $aliased_rdf_entity_ids);
          break;

        case 'update':
          // Process RDF entities that are in the list of URL aliases.
          $aliased_rdf_entity_ids = $this->getAliasedEntityIds($context['sandbox']);
          $context['sandbox']['ids_to_process'] = array_intersect($context['sandbox']['rdf_entity_ids'], $aliased_rdf_entity_ids);
          break;

        case 'all':
          $context['sandbox']['ids_to_process'] = $context['sandbox']['rdf_entity_ids'];
          break;

        default:
          $context['sandbox']['ids_to_process'] = [];

      }
      $context['sandbox']['total'] = count($context['sandbox']['ids_to_process']);
    }

    if (empty($context['sandbox']['ids_to_process'])) {
      $context['finished'] = 1;
      return;
    }

    $ids = array_splice($context['sandbox']['ids_to_process'], 0, 25);
    $updates = $this->bulkUpdate($ids);

    $context['sandbox']['count'] += count($ids);
    if ($updates !== 0) {
      $context['results']['updates'] += $updates;
    }

    $progress = sprintf('%.2f%%', $context['sandbox']['count'] / $context['sandbox']['total'] * 100);
    $context['message'] = $this->t('[@progress] Processed Rdf entity @id.', [
      '@progress' => $progress,
      '@id' => end($ids),
    ]);

    if ($context['sandbox']['count'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function batchDelete(&$context) {
    if (!isset($context['sandbox']['count'])) {
      $context['sandbox']['count'] = 0;
    }

    $aliased_rdf_entity_ids = $this->getAliasedEntityIds($context['sandbox']);

    // Get the total amount of items to process.
    if (!isset($context['sandbox']['total'])) {
      $context['sandbox']['total'] = count($aliased_rdf_entity_ids);

      // If there are no entities to delete, then stop immediately.
      if (!$context['sandbox']['total']) {
        $context['finished'] = 1;
        return;
      }
    }

    $to_delete = array_slice($aliased_rdf_entity_ids, $context['sandbox']['count'], 100, TRUE);
    $pids_by_id = array_flip($to_delete);

    PathautoState::bulkDelete($this->getEntityTypeId(), $pids_by_id);

    $context['sandbox']['count'] = min($context['sandbox']['count'] + 100, $context['sandbox']['total']);

    $progress = $context['sandbox']['count'] / $context['sandbox']['total'];
    $progress = sprintf('%.2f%%', $progress * 100);

    $context['message'] = $this->t('[@progress] Deleted alias for Rdf entity @id.', [
      '@progress' => $progress,
      '@id' => end($to_delete),
    ]);

    $context['results']['deletions'][] = $this->getLabel();

    if ($context['sandbox']['count'] != $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['count'] / $context['sandbox']['total'];
    }
  }

  /**
   * Returns the full list of RDF entity IDs.
   *
   * These are persisted in the batch operation sandbox.
   *
   * @param array $sandbox
   *   The batch operation sandbox.
   *
   * @return array
   *   An array of RDF entity IDs.
   */
  protected function getRdfEntityIds(array &$sandbox): array {
    if (empty($sandbox['rdf_entity_ids'])) {
      $sandbox['rdf_entity_ids'] = $this->getRdfEntityQuery()->execute();
    }

    return $sandbox['rdf_entity_ids'];
  }

  /**
   * Returns a Query object for RDF entities.
   *
   * @return \Drupal\Core\Entity\Query\QueryInterface
   *   The entity query.
   */
  protected function getRdfEntityQuery(): Query {
    /** @var \Drupal\sparql_entity_storage\SparqlEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('rdf_entity');
    return $storage->getQuery();
  }

  /**
   * Returns the url alias source paths that correspond with RDF entities.
   *
   * This data will be persisted in the batch operation sandbox.
   *
   * @param array $sandbox
   *   The batch operation sandbox.
   *
   * @return array
   *   An associative array of source paths, keyed by url alias ID.
   */
  protected function getSourcePaths(array &$sandbox): array {
    if (!isset($sandbox['source_paths'])) {
      $query = $this->database->select('path_alias', 'pa');
      $query->fields('pa', ['id', 'path']);
      $query->condition('path', '/rdf_entity/%', 'LIKE');
      $query->orderBy('pa.id');
      $source_paths = $query->execute()->fetchAllKeyed();

      // Filter out any source paths that point to subpaths of RDF entities.
      $sandbox['source_paths'] = preg_grep('|^/rdf_entity/.+/.+$|', $source_paths, PREG_GREP_INVERT);
    }

    return $sandbox['source_paths'];
  }

  /**
   * Converts the url alias source paths to RDF entity IDs.
   *
   * This will strip off the leading '/rdf_entity/' component, and decode the
   * ID.
   *
   * @param array $source_paths
   *   The source paths.
   *
   * @return array
   *   The converted RDF entity IDs.
   */
  protected function convertPathsToEntityIds(array $source_paths): array {
    return array_map(function ($source_path) {
      // Strip off the leading '/rdf_entity/' from the path and decode it.
      return UriEncoder::decodeUrl(substr($source_path, 12));
    }, $source_paths);
  }

  /**
   * Returns a list of RDF entity IDs that have a URL alias.
   *
   * This result will be persisted in the batch operation sandbox.
   *
   * @param array $sandbox
   *   The batch operation sandbox.
   *
   * @return array
   *   An array of RDF entity IDs.
   */
  protected function getAliasedEntityIds(array &$sandbox): array {
    // Get a list of all source paths that start with '/rdf_entity/' from the
    // URL alias table.
    $source_paths = $this->getSourcePaths($sandbox);

    // Convert the source paths to entity IDs.
    return $this->convertPathsToEntityIds($source_paths);
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeId() {
    // Pretend we're an alias type provided by the entity alias type deriver.
    // For the moment the support for tokens and bundles in patterns is
    // hardcoded to only work with derived alias types.
    // @see \Drupal\pathauto\Plugin\Deriver\EntityAliasTypeDeriver
    return $this->getEntityTypeId();
  }

}
