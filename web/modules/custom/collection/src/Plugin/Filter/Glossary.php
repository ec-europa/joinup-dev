<?php

declare(strict_types = 1);

namespace Drupal\collection\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\collection\Exception\MissingCollectionException;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\og\OgContextInterface;
use Drupal\og\OgGroupAudienceHelperInterface;
use Psr\Log\LoggerInterface;
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
   * The logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

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
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, OgContextInterface $og_context, EntityTypeManagerInterface $entity_type_manager, LoggerInterface $logger) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->ogContext = $og_context;
    $this->entityTypeManager = $entity_type_manager;
    $this->logger = $logger;
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
      $container->get('entity_type.manager'),
      $container->get('logger.channel.collection')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode): FilterProcessResult {
    $result = new FilterProcessResult($text);

    $collection = $this->getCollection();
    // A collection context cannot be detected.
    if (!isset($collection)) {
      return $result;
    }

    [$replacements, $cache_metadata] = $this->getReplacementsMap($collection);

    // This collection has no glossary term entries.
    if (empty($replacements)) {
      return $result->addCacheableDependency($cache_metadata);
    }

    $pattern = '/\b(' . implode('|', array_keys($replacements)) . ')\b/';

    // First, do a bird-eye check for glossary terms so that we avoid a heavy
    // processing if the text contains no term.
    if (!preg_match($pattern, $text)) {
      return $result->addCacheableDependency($cache_metadata);
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
      ->addCacheableDependency($cache_metadata);
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
   * @param \Drupal\collection\Entity\CollectionInterface $collection
   *   The collection for which to return the replacements map.
   *
   * @return array
   *   An indexed array (tuple) with two values:
   *     0. An associative array keyed by the glossary term or abbreviation. The
   *        values are arrays with two keys:
   *        - url: The glossary term URL.
   *        - summary: A summary to be used as tooltip.
   *     1. An object containing the cacheable metadata.
   */
  protected function getReplacementsMap(CollectionInterface $collection): array {
    // Make sure the filter cache invalidates when a new glossary term is added
    // in this collection.
    $cache_metadata = (new CacheableMetadata())
      ->addCacheTags(
        Cache::buildTags('og-group-content', $collection->getCacheTagsToInvalidate())
      );

    $map = [];
    $node_storage = $this->entityTypeManager->getStorage('node');
    $nids = $node_storage->getQuery()
      ->condition('type', 'glossary')
      ->condition(OgGroupAudienceHelperInterface::DEFAULT_FIELD, $collection->id())
      ->condition('status', TRUE)
      ->execute();

    if ($nids) {
      /** @var \Drupal\collection\Entity\GlossaryTermInterface $glossary */
      foreach ($node_storage->loadMultiple($nids) as $glossary) {
        $link = [
          'url' => $glossary->toUrl()->toString(),
          'summary' => $glossary->getSummary(),
        ];

        $map[$glossary->label()] = $link;
        // Link also the abbreviation, if any.
        if ($glossary->hasAbbreviation()) {
          $map[$glossary->getAbbreviation()] = $link;
        }

        // When this glossary node is changing, invalidate the filter cache.
        $cache_metadata->addCacheableDependency($glossary);
      }
    }

    return [$map, $cache_metadata];
  }

  /**
   * Retrieves the collection from the request context.
   *
   * This is not ideal but text filters have no direct way to retrieve the
   * context they belong to. We do not have access to the field that contains
   * the text.
   *
   * @see https://www.drupal.org/project/drupal/issues/226963
   *
   * @return \Drupal\collection\Entity\CollectionInterface|null
   *   The collection, or NULL if no collection could be derived from the
   *   context.
   */
  protected function getCollection(): ?CollectionInterface {
    $group = NULL;
    if ($group = $this->ogContext->getGroup()) {
      // Other kind of group? Maybe a solution which is collection content?
      if ($group instanceof CollectionContentInterface) {
        try {
          $group = $group->getCollection();
        }
        catch (MissingCollectionException $e) {
          // The content is orphaned. Log an error but allow the request to
          // continue, this is not fatal.
          $this->logger->error('Collection could not be retrieved from entity of type %type and ID %id', [
            '%type' => $group->getEntityTypeId(),
            '%id' => $group->id(),
          ]);
        }
      }
    }

    if ($group instanceof CollectionInterface) {
      return $group;
    }

    return NULL;
  }

}
