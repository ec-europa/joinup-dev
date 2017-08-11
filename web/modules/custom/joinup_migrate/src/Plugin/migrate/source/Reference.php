<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Path\AliasManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Utility\Error;
use Drupal\editor\Entity\Editor;
use Drupal\joinup_migrate\FileUtility;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePostRowSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrateProcessInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Drupal\migrate_run\DrushLogMigrateMessage;
use Drupal\rdf_entity\UriEncoder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
   * Image extensions.
   */
  const IMAGE_EXTENSIONS = ['gif', 'png', 'jpg', 'jpeg'];

  /**
   * Non-Drupal top-level directories that are providing files/images.
   */
  const NON_DRUPAL_SERVICES = [
    'mailman',
    'nexus',
    'site',
    'svn',
    'webdav',
  ];

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
   * Allowed file extensions.
   *
   * @var string[]
   */
  protected $fileExtensions;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * If we are using a mocked file-system.
   *
   * @var bool
   */
  protected $mockFileSystem;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The 'file:inline' migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $fileInlineMigration;

  /**
   * The 'file:inline' migration message handler.
   *
   * @var \Drupal\migrate\MigrateMessageInterface
   */
  protected $fileInlineMigrationMessage;

  /**
   * The 'file:inline' migration executable.
   *
   * @var \Drupal\migrate\MigrateExecutableInterface
   */
  protected $fileInlineMigrationExecutable;

  /**
   * The 'file:inline' migration ID map.
   *
   * @var \Drupal\migrate\Plugin\MigrateIdMapInterface
   */
  protected $fileInlineMigrationIdMap;

  /**
   * The 'file:inline' migration destination plugin.
   *
   * @var \Drupal\migrate\Plugin\migrate\destination\DestinationBase
   */
  protected $fileInlineMigrationDestinationPlugin;

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
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file-system service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrationPluginManagerInterface $migration_manager, Connection $db, EntityTypeManagerInterface $entity_type_manager, AliasManagerInterface $alias_manager, FileSystemInterface $file_system, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->migrationPluginManager = $migration_manager;
    $this->db = $db;
    $this->entityTypeManager = $entity_type_manager;
    $this->aliasManager = $alias_manager;
    $this->fileSystem = $file_system;
    $this->eventDispatcher = $event_dispatcher;

    // Set other, non-injected, internals.
    $this->mockFileSystem = Settings::get('joinup_migrate.mock_filesystem', TRUE);
    $this->fileInlineMigration = $this->migrationPluginManager->createInstance('file:inline');
    $this->fileInlineMigrationIdMap = $this->fileInlineMigration->getIdMap();
    $this->fileInlineMigrationMessage = new DrushLogMigrateMessage();
    $this->fileInlineMigrationExecutable = new MigrateExecutable($this->fileInlineMigration, $this->fileInlineMigrationMessage, $this->eventDispatcher);
    $this->fileInlineMigrationDestinationPlugin = $this->fileInlineMigration->getDestinationPlugin();
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
      $container->get('path.alias_manager'),
      $container->get('file_system'),
      $container->get('event_dispatcher')
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
    // As we've already created redirects for all migrated entities, we already
    // have a consistent mapping database between the old and the new URLs, in
    // the {redirect} table, so we simply perform a lookup there to see if we
    // can rewrite this path. We do not rewrite the canonical paths for the
    // entities that are preserving their IDs, so a community content, referred
    // as '/node/123', will not be rewritten but will continue to work because
    // the ID was preserved during the migration process.
    if (!$uri = $this->db->query(static::SQL, [':path' => $path])->fetchField()) {
      // This path may refer a static file that was not migrated as child of a
      // content entity. We try to migrate first that resource, if possible.
      if (!$this->importUnmigratedFile($path)) {
        return NULL;
      }
      // The file migration was successful. Normally a redirect has been
      // created. Let's try again.
      if (!$uri = $this->db->query(static::SQL, [':path' => $path])->fetchField()) {
        return NULL;
      }
    }

    // Get the source entity and remove the scheme from link.
    list($scheme, $link) = explode(':', $uri, 2);

    // A file-system path of a managed file.
    if ($scheme === 'base') {
      // Strip out '/sites/default/files/' prefix.
      $target_path = substr($link, 21);
      $values = ['uri' => "public://{$target_path}"];
      $files = $this->getStorage('file')->loadByProperties($values);
      if (!$files) {
        return NULL;
      }
      $entity = reset($files);
    }
    // An alias to an entity canonical path.
    elseif ($scheme === 'internal') {
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
   * Tries to migrate the static file referred by a given path.
   *
   * Content text fields may contain references to managed or unmanaged files
   * that were not migrated, mainly because their parent entity was not in the
   * scope of migration. In order to keep as many links valid as we can, we
   * migrate such files as CKEditor inline files (or images).
   *
   * @param string $path
   *   A local path that may refer a static file eligible for migration.
   *
   * @return bool
   *   If an attempt to migrate file referred by the path was successful.
   */
  protected function importUnmigratedFile($path) {
    if (!$type = $this->getFileType($path)) {
      return FALSE;
    }

    // Qualify the path.
    if ($this->isAcceptedNonDrupalServiceResource($path)) {
      // We don't have access to non-Drupal files, except through HTTP.
      $source_path = "https://joinup.ec.europa.eu/$path";
    }
    else {
      // Normal Drupal static files.
      $source_path = FileUtility::getLegacySiteFiles() . '/' . substr($path, 20);
      // Mock the file for testing purposes.
      $this->mockFile($source_path);
    }

    $values = [
      'fid' => $path,
      'path' => $source_path,
      'timestamp' => \Drupal::time()->getRequestTime(),
      'uid' => 1,
      'destination_uri' => 'public://inline-' . $type . '/' . pathinfo($path, PATHINFO_BASENAME),
    ] + $this->fileInlineMigration->getSourceConfiguration();

    $row = new Row($values, ['fid' => $path]);
    $row->rehash();

    // We are migrating the files by following the MigrateExecutable path,
    // including logging the success, errors and dispatching the migrate events.
    // @see \Drupal\migrate\MigrateExecutable::import()
    try {
      $this->fileInlineMigrationExecutable->processRow($row);
    }
    catch (MigrateException $e) {
      $this->fileInlineMigrationIdMap->saveIdMapping($row, [], $e->getStatus());
      $this->fileInlineMigrationIdMap->saveMessage($row->getSourceIdValues(), $e->getMessage(), $e->getLevel());
      return FALSE;
    }

    try {
      $this->eventDispatcher->dispatch(MigrateEvents::PRE_ROW_SAVE, new MigratePreRowSaveEvent($this->fileInlineMigration, $this->fileInlineMigrationMessage, $row));
      $destination_id_values = $this->fileInlineMigrationDestinationPlugin->import($row, $this->fileInlineMigrationIdMap->lookupDestinationId($row->getSourceIdValues()));
      $this->eventDispatcher->dispatch(MigrateEvents::POST_ROW_SAVE, new MigratePostRowSaveEvent($this->fileInlineMigration, $this->fileInlineMigrationMessage, $row, $destination_id_values));
      if ($destination_id_values) {
        $this->fileInlineMigrationIdMap->saveIdMapping($row, $destination_id_values);
      }
      else {
        $this->fileInlineMigrationIdMap->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
        if (!$this->fileInlineMigrationIdMap->messageCount()) {
          $message = $this->t('New object was not saved, no error provided');
          $this->fileInlineMigrationIdMap->saveMessage($row->getSourceIdValues(), $message);
          $this->fileInlineMigrationExecutable->message->display($message);
        }
      }
    }
    catch (MigrateException $e) {
      $this->fileInlineMigrationIdMap->saveIdMapping($row, [], $e->getStatus());
      $this->fileInlineMigrationIdMap->saveMessage($row->getSourceIdValues(), $e->getMessage(), $e->getLevel());
      return FALSE;
    }
    catch (\Exception $e) {
      $this->fileInlineMigrationIdMap->saveIdMapping($row, [], MigrateIdMapInterface::STATUS_FAILED);
      // Unfortunately \Drupal\migrate\MigrateExecutable::handleException() is
      // a protected method.
      // @see \Drupal\migrate\MigrateExecutable::handleException()
      $result = Error::decodeException($e);
      $message = $result['@message'] . ' (' . $result['%file'] . ':' . $result['%line'] . ')';
      $this->fileInlineMigrationIdMap->saveMessage($row->getSourceIdValues(), $message);
      $this->fileInlineMigrationExecutable->message->display($message, 'error');
      return FALSE;
    }

    return TRUE;
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
   *   - '/sites/default/files/inline-images/image.png' -> NULL.
   *   - '/sites/default/files/inline-files/doc.pdf' -> NULL.
   *
   * @param string $path
   *   The patch to be checked.
   *
   * @return string[]|null
   *   The relative path parts or NULL.
   */
  protected function getRelativePath($path) {
    if ((strpos($path, '#') === 0) || !UrlHelper::isValid(UrlHelper::encodePath($path))) {
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
    $path = ltrim($path, '/');

    // Remove prefixes such as '../../some/path'.
    $path = preg_replace('|^[\.\./]+|', '', $path);

    if (preg_match('@^sites/default/files/inline\-(files|images)@', $path)) {
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
   * Checks the path extension and return the type of resource: files or images.
   *
   * @param string $path
   *   The path to be checked.
   *
   * @return string|null
   *   The type of file ('files' or 'images') or NULL if it does not qualify as
   *   a file.
   */
  protected function getFileType($path) {
    // The path should be inside 'sites/default/files/' or inside one of the
    // accepted non-Drupal services.
    if (!preg_match('@^sites/default/files/@', $path) && !$this->isAcceptedNonDrupalServiceResource($path)) {
      return NULL;
    }

    // Don't accept paths with no extension.
    if (!$extension = pathinfo($path, PATHINFO_EXTENSION)) {
      return NULL;
    }
    $extension = Unicode::strtolower($extension);

    if (in_array($extension, static::IMAGE_EXTENSIONS, TRUE)) {
      return 'images';
    }

    // Get and cache file extensions from the editor settings.
    if (!isset($this->fileExtensions)) {
      $editor = Editor::load('content_editor');
      $this->fileExtensions = explode(' ', $editor->getThirdPartySetting('editor_file', 'extensions'));
    }

    if (in_array($extension, $this->fileExtensions, TRUE)) {
      return 'files';
    }

    return NULL;
  }

  /**
   * Checks is a resource is provided by an accepted non-Drupal service.
   *
   * @param string $path
   *   The resource path to be checked.
   *
   * @return bool
   *   If the resource is provided by an accepted non-Drupal service.
   */
  protected function isAcceptedNonDrupalServiceResource($path) {
    list($top_level_path) = explode('/', $path, 2);
    return in_array($top_level_path, static::NON_DRUPAL_SERVICES, TRUE);
  }

  /**
   * Mocks a source file for testing purposes.
   *
   * @param string $source_path
   *   The full file path.
   */
  protected function mockFile($source_path) {
    if ($this->mockFileSystem) {
      $path_parts = pathinfo($source_path);
      if (!is_dir($path_parts['dirname'])) {
        $this->fileSystem->mkdir($path_parts['dirname'], NULL, TRUE);
      }
      if (!file_exists($source_path)) {
        // Create a '0 size' file.
        touch($source_path);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'reference';
  }

}
