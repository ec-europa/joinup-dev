<?php

declare(strict_types = 1);

namespace Drupal\collection\Controller;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\node\NodeInterface;
use Drupal\views\Views;

/**
 * Provides controllers for Joinup glossary functionality.
 */
class GlossaryController extends ControllerBase {

  /**
   * Static cache of glossary term first letters.
   *
   * @var string[]
   */
  protected $letters;

  /**
   * Provides an access callback for the 'collection.glossary_page' route.
   *
   * @param \Drupal\collection\Entity\CollectionInterface $rdf_entity
   *   The collection entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(CollectionInterface $rdf_entity): AccessResultInterface {
    $tags = Cache::buildTags('og-group-content', $rdf_entity->getCacheTagsToInvalidate());
    return AccessResult::allowedIf($this->hasGlossaryTerms($rdf_entity))
      ->addCacheTags($tags);
  }

  /**
   * Provides a controller for the 'collection.glossary_page' route.
   *
   * @param \Drupal\collection\Entity\CollectionInterface $rdf_entity
   *   The collection entity.
   * @param string|null $letter
   *   (optional) An optional letter to filter the glossary on.
   *
   * @return array
   *   The output render array.
   */
  public function glossary(CollectionInterface $rdf_entity, ?string $letter = NULL): array {
    $navigator = [
      '#theme' => 'glossary_navigator',
      '#url' => Url::fromRoute('collection.glossary_page', [
        'rdf_entity' => $rdf_entity->id(),
      ])->toString(),
      '#letters' => $this->getGlossaryTermLetters($rdf_entity),
      '#current' => $letter,
    ];

    $view = Views::getView('glossary')->executeDisplay('main', [
      $rdf_entity->id(),
      $letter,
    ]);
    unset($view['#cache']['max-age']);

    return [
      [
        $navigator,
        $view,
        $navigator,
      ],
      '#cache' => [
        'tags' => Cache::buildTags('og-group-content', $rdf_entity->getCacheTagsToInvalidate()),
      ],
    ];
  }

  /**
   * Returns the glossary page title.
   *
   * @param \Drupal\collection\Entity\CollectionInterface $rdf_entity
   *   The collection entity.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The translated title.
   */
  public function title(CollectionInterface $rdf_entity): MarkupInterface {
    return $this->t('@group glossary', ['@group' => $rdf_entity->label()]);
  }

  /**
   * Finds out if the collection has glossary term entries.
   *
   * @param \Drupal\collection\Entity\CollectionInterface $rdf_entity
   *   The collection entity.
   *
   * @return bool
   *   If the collection has glossary term entries.
   */
  protected function hasGlossaryTerms(CollectionInterface $rdf_entity): bool {
    return (bool) $this->getGlossaryTermLetters($rdf_entity);
  }

  /**
   * Returns a list of unique uppercase letters extracted from glossary terms.
   *
   * @param \Drupal\collection\Entity\CollectionInterface $rdf_entity
   *   The collection entity.
   *
   * @return string[]
   *   Grouped list of the first letter of each glossary term, transformed to
   *   uppercase.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown when the node entity plugin definition is invalid.
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *   Thrown when the node entity type is not defined.
   */
  protected function getGlossaryTermLetters(CollectionInterface $rdf_entity): array {
    if (!isset($this->letters)) {
      $this->letters = [];
      $storage = $this->entityTypeManager()->getStorage('node');
      $nids = $storage->getQuery()
        ->condition('type', 'glossary')
        ->condition('og_audience', $rdf_entity->id())
        ->condition('status', TRUE)
        ->execute();
      if ($nids) {
        $this->letters = array_values(
          array_unique(
            array_map(
              function (NodeInterface $term): string {
                return mb_strtoupper(substr(trim($term->label()), 0, 1));
              }, $storage->loadMultiple($nids)
            )
          )
        );
        sort($this->letters);
      }
    }
    return $this->letters;
  }

}
