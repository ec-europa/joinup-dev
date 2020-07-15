<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\node\NodeInterface;
use Drupal\og\OgContextInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Wraps glossary terms with links to their definition page.
 *
 * @Filter(
 *   id = "collection_glossary",
 *   title = @Translation("Collection glossary"),
 *   description = @Translation("Replaces glossary terms with their link version."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 * )
 */
class Glossary extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * The OG context service.
   *
   * @var \Drupal\og\OgContextInterface
   */
  protected $ogContext;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The collection.
   *
   * @var \Drupal\collection\Entity\CollectionInterface
   */
  protected $collection;

  /**
   * The cache metadata to be applied.
   *
   * @var \Drupal\Core\Cache\CacheableMetadata
   */
  protected $cacheMetadata;

  /**
   * Constructs a new filter plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\og\OgContextInterface $og_context
   *   The OG context service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, OgContextInterface $og_context, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogContext = $og_context;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('og.context'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $this->setCollection();

    $result = new FilterProcessResult($text);

    // A collection context cannot be detected.
    if (!isset($this->collection)) {
      return $result;
    }

    // This collection has no glossary term entries.
    if (!$replacements = $this->getReplacementsMap()) {
      return $result->addCacheableDependency($this->cacheMetadata);
    }

    $pattern = '/\b(' . implode('|', array_keys($replacements)) . ')\b/';

    // First, do a bird-eye check for glossary terms so that we avoid a heavy
    // processing if the text contains no term.
    if (!preg_match($pattern, $text)) {
      return $result->addCacheableDependency($this->cacheMetadata);
    }

    $document = Html::load($text);
    $text_nodes = (new \DOMXPath($document))->evaluate("//text()");
    foreach ($text_nodes as $text_node) {
      // Avoid adding nested links.
      if ($this->isLinkText($text_node)) {
        continue;
      }

      $text_parts = preg_split($pattern, $text_node->nodeValue, -1, PREG_SPLIT_DELIM_CAPTURE);
      $parent_node = $text_node->parentNode;

      foreach ($text_parts as $text_part) {
        if (!isset($replacements[$text_part])) {
          $parent_node->insertBefore($document->createTextNode($text_part), $text_node);
          continue;
        }

        // @todo Consider replacing the link with a JavaScript tooltip.
        $link = $document->createElement('a', $text_part);
        $link->setAttribute('href', $replacements[$text_part]['url']);
        $link->setAttribute('class', 'glossary-term');
        $link->setAttribute('title', $replacements[$text_part]['summary']);
        $parent_node->insertBefore($link, $text_node);
      }
      $parent_node->removeChild($text_node);
    }

    return $result
      ->setProcessedText($document->saveHTML())
      ->addCacheableDependency($this->cacheMetadata);
  }

  /**
   * Finds-out if a piece of text is inside a HTML link.
   *
   * @param \DOMText $text_node
   *   The text node to be checked.
   *
   * @return bool
   *   If the given text node is inside a HTML link.
   */
  protected function isLinkText(\DOMText $text_node): bool {
    $node = $text_node;
    do {
      $node = $node->parentNode;
      if (!$node->parentNode) {
        return FALSE;
      }
    } while ($node->tagName !== 'a');
    return TRUE;
  }

  /**
   * Builds and returns a replacements map.
   *
   * @return array
   *   Associative array keyed by the glossary term or abbreviation. The values
   *   are arrays with two keys:
   *   - url: The glossary term URL.
   *   - summary: A summary to be used as tooltip.
   */
  protected function getReplacementsMap(): array {
    // Make sure the filter cache invalidates when a new glossary term is added
    // in this collection.
    $this->cacheMetadata = (new CacheableMetadata())
      ->addCacheTags(
        Cache::buildTags('og-group-content', $this->collection->getCacheTagsToInvalidate())
      );

    $map = [];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'glossary')
      ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $this->collection->id())
      ->condition('status', TRUE)
      ->execute();

    if ($nids) {
      /** @var \Drupal\node\NodeInterface $glossary */
      foreach ($node_storage->loadMultiple($nids) as $glossary) {
        $link = [
          'url' => $glossary->toUrl()->toString(),
          'summary' => $this->getSummaryOrTrimmedDefinition($glossary),
        ];

        $map[$glossary->label()] = $link;
        // Link also the abbreviation, if any.
        if (!$glossary->get('field_glossary_abbreviation')->isEmpty()) {
          $map[$glossary->get('field_glossary_abbreviation')->value] = $link;
        }

        // When this glossary node is changing, invalidate the filter cache.
        $this->cacheMetadata->addCacheableDependency($glossary);
      }
    }

    return $map;
  }

  /**
   * Stores the collection context.
   */
  protected function setCollection(): void {
    if ($this->collection = $this->ogContext->getGroup()) {
      // Other kind of group?
      if (!$this->collection instanceof CollectionInterface) {
        $this->collection = $this->collection->getCollection();
      }
    }
  }

  /**
   * Returns either the glossary definition summary or a trimmed part of it.
   *
   * @param \Drupal\node\NodeInterface $glossary
   *   The glossary term entity.
   *
   * @return string
   *   The summary.
   */
  protected function getSummaryOrTrimmedDefinition(NodeInterface $glossary): string {
    $definition = $glossary->get('field_glossary_definition');
    if (!empty($definition->summary)) {
      return trim($definition->summary);
    }
    $summary = text_summary($definition->value, $definition->format, 300);
    return trim(strip_tags($summary));
  }

}
