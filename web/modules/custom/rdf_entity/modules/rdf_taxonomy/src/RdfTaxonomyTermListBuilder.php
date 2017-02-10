<?php

namespace Drupal\rdf_taxonomy;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Render\RendererInterface;
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
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

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
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RouteMatchInterface $route_match, RendererInterface $renderer) {
    parent::__construct($entity_type, $storage);
    $this->routeMatch = $route_match;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('current_route_match'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityIds() {
    return $this->getStorage()->getQuery()
      ->condition('vid', $this->getVocabulary()->id())
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    return $this->getStorage()->loadTree($this->getVocabulary()->id(), 0, NULL, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    return [
      'name' => $this->t('Term'),
    ] + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $indent = ['#theme' => 'indentation', '#size' => $entity->depth];
    return [
      'name' => [
        'data' => [
          '#prefix' => $this->renderer->render($indent),
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
