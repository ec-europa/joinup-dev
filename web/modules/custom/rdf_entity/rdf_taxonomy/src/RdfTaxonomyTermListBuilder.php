<?php

namespace Drupal\rdf_taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * View builder for taxonomy terms stored in triple store.
 */
class RdfTaxonomyTermListBuilder extends EntityListBuilder {

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The vocabulary for this term listing.
   *
   * @var \Drupal\taxonomy\VocabularyInterface
   */
  protected $vocabulary;

  /**
   * Constructs a new RdfTaxonomyTermListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match) {
    parent::__construct($entity_type, $storage);
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->condition('vid', $this->getVocabulary()->id());

    // @todo Use pager when \Drupal\rdf_entity\Entity\Query\Sparql\Query knows
    //   sorting.
    // Only add the pager if a limit is specified.
    // if ($this->limit) {
    // $query->pager($this->limit);
    // }
    return $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    // @todo Implement sorting in \Drupal\rdf_entity\Entity\Query\Sparql\Query
    //   and drop this implementation of ::load()
    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = $this->getStorage();

    $loaded = parent::load();
    $tree = [];
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($loaded as $tid => $term) {
      $parent = $storage->loadParents($term->id());
      $parent = reset($parent);
      $parent_label = $parent ? $parent->label() : '';
      $tree[$parent_label][(string) $term->label()] = $tid;
    }

    ksort($tree, SORT_NATURAL | SORT_FLAG_CASE);
    $sorted = [];
    foreach ($tree as $parent_label => $terms) {
      ksort($terms, SORT_NATURAL | SORT_FLAG_CASE);
      foreach ($terms as $tid) {
        $sorted[$tid] = $loaded[$tid];
      }
    }

    return $sorted;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'parent' => $this->t('Parent'),
      'name' => $this->t('Term'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = $this->getStorage();
    $parents = $storage->loadParents($entity->id());
    $parent = reset($parents);
    if ($parent) {
      $parent_render = [
        '#type' => 'link',
        '#title' => $parent->label(),
        '#url' => $parent->toUrl(),
      ];
    }
    else {
      $parent_render = ['#plain_text' => $this->t('<root>')];
    }

    return [
      'parent' => [
        'data' => $parent_render,
      ],
      'name' => [
        'data' => [
          '#type' => 'link',
          '#title' => $entity->label(),
          '#url' => $entity->toUrl(),
        ],
      ],
    ] + parent::buildRow($entity);
  }

  /**
   * Gets the vocabulary entity for this listing.
   *
   * @return \Drupal\taxonomy\VocabularyInterface
   *   The vocabulary config entity.
   *
   * @throws \Exception
   *   When on a malformed path, with no valid vocabulary ID.
   */
  protected function getVocabulary() {
    if (!isset($this->vocabulary)) {
      if (!$this->vocabulary = $this->routeMatch->getParameter('taxonomy_vocabulary')) {
        throw new \Exception("Valid vocabulary missed from URL.");
      }
    }
    return $this->vocabulary;
  }

}
