<?php

declare(strict_types = 1);

namespace Drupal\joinup_user;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\search_api\Entity\SearchApiConfigEntityStorage;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\Utility;

/**
 * Service to retrieve the entities that were created by a user.
 */
class EntityAuthorshipHelper implements EntityAuthorshipHelperInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs an EntityAuthorshipHelper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIdsAuthoredByUser($user_id, array $publication_states = ['published']): array {
    assert(empty(array_diff($publication_states, ['published', 'unpublished'])), 'Valid publication states are "published" and "unpublished".');

    $entity_ids = [];
    foreach ($publication_states as $publication_state) {
      // Published and unpublished content are stored in separate indexes.
      $index = $this->getSearchIndex($publication_state);
      $query = $index->query();
      $query->addCondition('entity_author', $user_id);
      $results = $query->execute();

      foreach ($results->getResultItems() as $result) {
        // Prune out the entity type and ID from the Search API combined ID.
        list($datasource_id, $raw_id) = Utility::splitCombinedId($result->getId());
        $datasource = $index->getDatasource($datasource_id);
        $entity_type_id = $datasource->getEntityTypeId();
        list($entity_id, $langcode) = explode(':', $raw_id);
        $entity_ids[$entity_type_id][$entity_id] = $entity_id;
      }
    }

    return $entity_ids;
  }

  /**
   * Returns the search index with the given ID.
   *
   * @param string $id
   *   The index ID.
   *
   * @return \Drupal\search_api\IndexInterface
   *   The search index.
   */
  protected function getSearchIndex(string $id): IndexInterface {
    return $this->getSearchApiIndexStorage()->load($id);
  }

  /**
   * Returns the Search API index storage handler.
   *
   * @return \Drupal\search_api\Entity\SearchApiConfigEntityStorage
   *   The storage handler.
   */
  protected function getSearchApiIndexStorage(): SearchApiConfigEntityStorage {
    return $this->entityTypeManager->getStorage('search_api_index');
  }

}
