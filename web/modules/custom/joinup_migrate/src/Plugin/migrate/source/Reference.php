<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\rdf_entity\UriEncoder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Updates references to entities inside body fields.
 *
 * @MigrateSource(
 *   id = "reference"
 * )
 */
class Reference extends SourcePluginBase implements ContainerFactoryPluginInterface {

  /**
   * Tags to be scanned for URLs.
   */
  const TAGS = ['a' => 'href', 'img' => 'src'];

  /**
   * SQL query.
   */
  const SQL = "SELECT redirect_redirect__uri AS uri FROM {redirect} WHERE redirect_source__path = :path";

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The path alias manager service.
   *
   * @var \Drupal\Core\Path\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * Static cache for entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface[]
   */
  protected $storage = [];

  /**
   * Static cache for processed results.
   *
   * @var array
   */
  protected $result = [];

  /**
   * A list of fields keyed by entity type and bundle.
   *
   * @var array[]
   */
  protected $fieldInfo;

  /**
   * Constructs a new Reference process plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The the migration plugin.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_manager
   *   The migration manager service.
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Path\AliasManagerInterface $alias_manager
   *   The path alias manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_manager, Connection $db, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->migrationPluginManager = $migration_manager;
    $this->db = $db;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migration'),
      $container->get('database'),
      $container->get('entity_type.manager'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'entity_type_id' => [
        'type' => 'string',
      ],
      'entity_id' => [
        'type' => 'string',
        'length' => 2048,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'entity_type_id' => $this->t('Entity type'),
      'bundle' => $this->t('Bundle'),
      'entity_id' => $this->t('Entity ID'),
      'values' => $this->t('Values'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $rows = [];

    foreach ($this->getFieldInfo() as $entity_type_id => $field_info) {
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $bundle_key = $entity_type->getKey('bundle');
      $query = $this->getStorage($entity_type_id)->getQuery();
      $query->condition($bundle_key, array_keys($field_info), 'IN');

      foreach ($query->execute() as $entity_id) {
        $rows[] = [
          'entity_type_id' => $entity_type_id,
          'entity_id' => $entity_id,
        ];
      }
    }

    return new \ArrayIterator($rows);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $entity_type_id = $row->getSourceProperty('entity_type_id');
    $entity_id = $row->getSourceProperty('entity_id');
    $entity = $this->getStorage($entity_type_id)->load($entity_id);
    $bundle = $entity->bundle();
    $row->setSourceProperty('bundle', $bundle);

    $values = [];
    foreach ($this->getFieldInfo($entity_type_id)[$bundle] as $field) {
      $value = $entity->get($field)->value;
      if ($value && $this->process($value)) {
        $values[$field] = $value;
      }
    }
    $row->setSourceProperty('values', $values);

    return parent::prepareRow($row);
  }

  /**
   * Replaces the links with new links, pointing to the migrated objects.
   *
   * @param string $markup
   *   The markup string to be altered, passed by reference.
   *
   * @return bool
   *   If $markup has been changed.
   */
  protected function process(&$markup) {
    // Perform a bird-eye check and exit here, if there are no internal links,
    // for performance reasons.
    if (!static::needsProcessing($markup)) {
      return FALSE;
    }

    // Build the DOM based on this markup.
    $document = Html::load($markup);
    $changed = FALSE;
    foreach (static::TAGS as $tag => $attribute) {
      /** @var \DOMElement $element */
      foreach ($document->getElementsByTagName($tag) as $element) {
        $incoming_path = $element->getAttribute($attribute);
        if ($incoming_path && $parts = $this->getRelativePath($incoming_path)) {
          // If not cached, try to extract a valid target and cache it.
          if (!array_key_exists($incoming_path, $this->result)) {
            $this->result[$incoming_path] = $this->processPath($parts['path']);
          }
          if ($this->result[$incoming_path]) {
            $link = $this->result[$incoming_path]['link'];
            if (!empty($parts['fragment'])) {
              $link .= "#{$parts['fragment']}";
            }
            $element->setAttribute($attribute, $link);
            $element->setAttribute('data-entity-type', $this->result[$incoming_path]['type']);
            $element->setAttribute('data-entity-uuid', $this->result[$incoming_path]['uuid']);

            // Change also the text on elements like:
            // @code
            // <a href="http://joinup.ec.europa.eu/some/path">http://joinup.ec.europa.eu/some/path</a>
            // @endcode
            if (preg_match('@^(http[s]?://)@', $element->textContent, $found)) {
              $qualified_incoming_path = $incoming_path;
              if (!preg_match('@^http[s]?://@', $incoming_path)) {
                $qualified_incoming_path = ltrim($qualified_incoming_path, '/');
                $qualified_incoming_path = "{$found[1]}joinup.ec.europa.eu/$qualified_incoming_path";
              }
              if ($element->textContent === $qualified_incoming_path) {
                $element->nodeValue = "{$found[1]}joinup.ec.europa.eu$link";
              }
            }

            $changed = TRUE;
          }
        }
      }
    }
    if ($changed) {
      $markup = Html::serialize($document);
    }

    return $changed;
  }

  /**
   * Processes a path.
   *
   * @param string $path
   *   The relative path.
   *
   * @return string[]|null
   *   An associative array with the next keys: 'link', 'type' and 'uuid' or
   *   NULL if the path doesn't target a migrated entity.
   *
   * @throws \RuntimeException
   *   If the URI of destination object is malformed.
   */
  protected function processPath($path) {
    if (!$uri = $this->db->query(static::SQL, [':path' => $path])->fetchField()) {
      return NULL;
    }

    // Get the source entity and remove the scheme from link.
    list($scheme, $link) = explode(':', $uri, 2);
    if ($scheme === 'base') {
      // A managed file system path.
      $target_path = substr($link, 21);
      $values = ['uri' => "public://{$target_path}"];
      $files = $this->getStorage('file')->loadByProperties($values);
      if (!$files) {
        return NULL;
      }
      $entity = reset($files);
    }
    elseif ($scheme === 'internal') {
      // An entity.
      list($entity_type_id, $entity_id) = explode('/', substr($link, 1), 2);
      // RDF entity IDs are encoded.
      if ($entity_type_id === 'rdf_entity') {
        $entity_id = UriEncoder::decodeUrl($entity_id);
      }
      if (!$entity = $this->getStorage($entity_type_id)->load($entity_id)) {
        return NULL;
      }
    }
    else {
      // Malformed.
      throw new \RuntimeException("Malformed uri: $uri");
    }

    return [
      // Try to alias the link.
      'link' => $this->aliasManager->getAliasByPath($link),
      'type' => $entity->getEntityTypeId(),
      'uuid' => $entity->uuid(),
    ];
  }

  /**
   * Returns the relative part from a locally passed path as URL parts.
   *
   * Checks if the passed path points to this site and extracts the relative
   * part. If the path points to other site or to CKEditor files, will return
   * NULL. The result is split in URL parts. Examples for the 'path' part:
   *   - 'http://example.com/some/path' -> NULL,
   *   - 'http://joinup.ec.europa.eu/some/path' -> 'some/path',
   *   - 'https://joinup.ec.europa.eu/some/path' -> 'some/path',
   *   - '/some/path' -> 'some/path',
   *   - 'some/path' -> 'some/path',
   *   - '/sites/default/files/some/path' -> 'some/path',
   *   - '/sites/default/files/ckeditor_files/images/image.png' -> NULL.
   *
   * @param string $path
   *   The patch to be checked.
   *
   * @return string[]|null
   *   The relative path parts or NULL.
   */
  protected function getRelativePath($path) {
    if ((strpos($path, '#') === 0) || !UrlHelper::isValid($path)) {
      // Only fragment or invalid.
      return NULL;
    }

    if (!$url_parts = parse_url($path)) {
      // Malformed URL?
      return NULL;
    }

    $scheme = 'http://';
    if (!empty($url_parts['scheme'])) {
      if (!in_array($url_parts['scheme'], ['http', 'https'])) {
        // Only HTTP and HTTPS are allowed.
        return NULL;
      }
      $scheme = "{$url_parts['scheme']}://";
    }

    if (UrlHelper::isExternal($path)) {
      if (preg_match('@^(//)?joinup.ec.europa.eu@', $path)) {
        $path = $scheme . ltrim($path, '/');
        // Re-extract parts.
        if (!$url_parts = parse_url($path)) {
          return NULL;
        }
      }

      try {
        if (!UrlHelper::externalIsLocal($path, "{$scheme}joinup.ec.europa.eu")) {
          return NULL;
        }
      }
      catch (\Exception $e) {
        return NULL;
      }

      // Remove the host part.
      $path = preg_replace('|^http[s]?://joinup.ec.europa.eu(.*)$|', '$1', $path);
    }
    // Remove the base path from the beginning.
    global $base_path;
    if (Unicode::strpos($path, $base_path) === 0) {
      $path = Unicode::substr($path, Unicode::strlen($base_path));
    }

    // Remove prefixes such as '../../some/path'.
    $path = preg_replace('|^[\.\./]+|', '', $path);

    if (Unicode::strpos($path, 'sites/default/files/ckeditor_files/') === 0) {
      // CKEditor files are handled in a different way.
      return NULL;
    }

    return parse_url($path) ?: NULL;
  }

  /**
   * Preforms a bird-eye check on the markup to see if processing is needed.
   *
   * This method is called just to avoid processing on markup that doesn't
   * really need processing and improving the performance.
   *
   * @param string $markup
   *   The markup to be checked.
   *
   * @return bool
   *   TRUE, if process is needed.
   */
  protected static function needsProcessing($markup) {
    $a_pattern = "@<a\s+[^>]*href\s*=\s*(['\"])??((http|https)?://joinup.ec.europa.eu)?[/]?([^\\1]*?)\\1[^>]*>@i";
    $img_pattern = "@<img\s+[^>]*src\s*=\s*(['\"])??((http|https)?://joinup.ec.europa.eu)?[/]?([^\\1]*?)\\1[^>]*>@i";
    return preg_match($a_pattern, $markup) || preg_match($img_pattern, $markup);
  }

  /**
   * Returns the fields to be migrated in a structured array.
   *
   * A list of entity types, as keys. The values are markup fields grouped by
   * bundle under each entity:
   * @code
   * [
   *   'node' => [
   *     'article' => ['body', 'field_foo'],
   *     'page' => ['body'],
   *    ],
   *   'other_entity_type' => [
   *      'some_bundle' => ['field_bar', 'field_baz'],
   *      ...,
   *   ],
   *   ...,
   * ]
   * @endcode
   * The list of fields is built by checking if the field uses the 'file_inline'
   * process plugin.
   *
   * @param string $limit_to_entity_type_id
   *   (optional) If passed, only information regarding that entity will be
   *   returned. Defaults to null.
   *
   * @return array[]
   *   A structured array.
   */
  protected function getFieldInfo($limit_to_entity_type_id = NULL) {
    if (!isset($this->fieldInfo)) {
      $this->fieldInfo = [];
      // Iterate over all migrations.
      foreach ($this->migrationPluginManager->createInstances([]) as $migration) {
        $definition = $migration->getPluginDefinition();
        // But only on those having an entity destination.
        if (strpos($definition['destination']['plugin'], 'entity:') === 0) {
          $entity_type_id = substr($definition['destination']['plugin'], 7);
          $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
          // The entity type must support bundles.
          if ($bundle_key = $entity_type->getKey('bundle')) {
            $bundles = [];
            // The bundle is passed as a field mapping.
            if (isset($definition['process'][$bundle_key])) {
              // Get all bundles for this entity type.
              if ($bundle_entity_type_id = $entity_type->getBundleEntityType()) {
                $bundle_storage = $this->entityTypeManager->getStorage($bundle_entity_type_id);
                $bundles = array_keys($bundle_storage->loadMultiple());
              }
            }
            // A default bundle is configured in for the destination plugin.
            elseif (!empty($definition['destination']['default_bundle'])) {
              $bundles = [$definition['destination']['default_bundle']];
            }

            foreach ($bundles as $bundle) {
              if (!empty($this->fieldInfo[$entity_type_id][$bundle])) {
                // This bundle was already processed in a previous iteration.
                continue;
              }

              // Collect all fields that are using the 'file_inline' processor.
              $fields = array_keys(array_filter($migration->getProcessPlugins(), function (array $plugins) {
                return (bool) array_filter($plugins, function (MigrateProcessInterface $plugin) {
                  return $plugin->getPluginId() === 'file_inline';
                });
              }));
              if ($fields) {
                // Remove the column: 'body/value' -> 'body'.
                $this->fieldInfo[$entity_type_id][$bundle] = array_map(function ($field) {
                  list($field,) = explode('/', $field, 2);
                  return $field;
                }, $fields);
              }
            }
          }
        }
      }
    }

    return $limit_to_entity_type_id ? $this->fieldInfo[$limit_to_entity_type_id] : $this->fieldInfo;
  }

  /**
   * Gets the storage of a given entity type ID.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   The entity storage object.
   */
  protected function getStorage($entity_type_id) {
    if (!isset($this->storage[$entity_type_id])) {
      $this->storage[$entity_type_id] = $this->entityTypeManager->getStorage($entity_type_id);
    }
    return $this->storage[$entity_type_id];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'reference';
  }

}
