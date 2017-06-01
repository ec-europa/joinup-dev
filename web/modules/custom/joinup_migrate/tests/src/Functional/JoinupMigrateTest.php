<?php

namespace Drupal\Tests\joinup_migrate\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;
use Drupal\Tests\rdf_entity\Traits\EntityUtilityTrait;

/**
 * Tests Joinup migration.
 *
 * @group joinup
 */
class JoinupMigrateTest extends BrowserTestBase implements MigrateMessageInterface {

  use EntityUtilityTrait;
  use RdfDatabaseConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected $profile = 'joinup';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['joinup_migrate'];

  /**
   * Migration messages collector.
   *
   * @var array[]
   */
  protected $messages = [];

  /**
   * Main database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * Legacy database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $legacyDb;

  /**
   * Migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $manager;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->setUpSparql();
    $this->setUpLegacyDb();

    parent::setUp();

    $this->db = Database::getConnection();

    // Prepare migration environment.
    $this->setUpMigration();

    // Run test migrations.
    $this->executeMigrations();
  }

  /**
   * Tests Joinup migrate suite.
   */
  public function test() {
    // Check that the migration was clean.
    if (!empty($this->messages)) {
      $messages = array_map(function ($message) {
        return "- $message";
      }, $this->messages);
      $this->fail("Error messages received during migrations:\n" . implode("\n", $messages));
    }

    // Common used objects.
    /* @var \Drupal\rdf_entity\RdfInterface $collection */
    $new_collection = $this->loadEntityByLabel('rdf_entity', 'New collection');

    // Assertions for each migrations are defined under assert/ directory.
    foreach (file_scan_directory(__DIR__ . '/assert', '|\.php$|') as $file) {
      require __DIR__ . '/assert/' . $file->filename;
    }
  }

  /**
   * Runs all available migrations.
   */
  protected function executeMigrations() {
    foreach ($this->manager->createInstances([]) as $id => $migration) {
      $this->executeMigration($migration, $id);
    }
  }

  /**
   * Executes a single migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to execute.
   * @param string $migration_id
   *   The migration ID (not used, just an artifact of array_walk()).
   * @param bool $execute_dependencies
   *   (optional) Whether to execute or not the dependent migrations. Defaults
   *   to FALSE.
   */
  protected function executeMigration(MigrationInterface $migration, $migration_id, $execute_dependencies = FALSE) {
    if ($execute_dependencies) {
      $dependencies = $migration->getMigrationDependencies();
      $required_ids = isset($dependencies['required']) ? $dependencies['required'] : NULL;
      if ($required_ids) {
        $required_migrations = $this->manager->createInstances($required_ids);
        array_walk($required_migrations, [$this, 'executeMigration'], $execute_dependencies);
      }
    }
    // Force running the migration, even the prior migrations were incomplete.
    $migration->set('requirements', []);

    (new MigrateExecutable($migration, $this))->import();
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status') {
    $this->messages[] = "$type: $message";
  }

  /**
   * Creates a connection to the legacy databases.
   *
   * @throws \Exception
   *   When environment variable SIMPLETEST_LEGACY_DB is not defined.
   */
  protected function setUpLegacyDb() {
    if (!$db_url = getenv('SIMPLETEST_LEGACY_DB')) {
      throw new \Exception('No migrate database connection. You must provide a SIMPLETEST_LEGACY_DB environment variable.');
    }
    $database = Database::convertDbUrlToConnectionInfo($db_url, dirname(dirname(__FILE__)));
    // We set the timezone to UTC to force MySQL time functions to correctly
    // convert timestamps into date/time.
    $database['init_commands'] = [
      'set_timezone_to_utc' => "SET time_zone='+00:00';",
    ];
    Database::addConnectionInfo('migrate', 'default', $database);
    $this->legacyDb = Database::getConnection('default', 'migrate');
  }

  /**
   * Prepares the environment to run the migrations.
   *
   * @throws \Exception
   *   When the legacy site webroot is not specified.
   */
  protected function setUpMigration() {
    // Set the legacy site webroot.
    $public_directory = $this->container->get('stream_wrapper.public')->getDirectoryPath();
    $legacy_webroot = "$public_directory/joinup_migrate/webroot";

    // Ensure settings.php settings.
    $settings['settings'] = [
      'joinup_migrate.mode' => (object) [
        'value' => 'test',
        'required' => TRUE,
      ],
      'joinup_migrate.source.root' => (object) [
        'value' => $legacy_webroot,
        'required' => TRUE,
      ],
    ];

    $settings_file = \Drupal::service('site.path') . '/settings.php';

    // Settings file is readonly at the moment.
    chmod($settings_file, 0666);
    drupal_rewrite_settings($settings, $settings_file);
    // Restore original permissions to the settings file.
    chmod($settings_file, 0444);

    $this->manager = $this->container->get('plugin.manager.migration');

    // Run the 'prepare' migration to assure data for MySQL views, needed by
    // self::createTestFiles() method.
    // @see \Drupal\Tests\joinup_migrate\Functional\JoinupMigrateTest::executeMigration()
    $migration = $this->manager->createInstance('prepare');
    $this->executeMigration($migration, $migration->id(), TRUE);

    $this->createTestFiles($legacy_webroot);
  }

  /**
   * Mocks a list of zero sized files to be imported during the test.
   *
   * @param string $base_dir
   *   The base directory for referring testing files.
   */
  protected function createTestFiles($base_dir) {
    $files = [];

    // Add 'discussion', 'event', 'news' attachments.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_attachment', 'a')
        ->fields('a', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'collection' logos.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_collection', 'c')
        ->fields('c', ['logo'])
        ->isNotNull('c.logo')
        ->condition('c.logo', 'sites/default/files/%', 'LIKE')
        ->execute()
        ->fetchCol()
    );

    // Add 'comment' files.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_comment_file', 'f')
        ->fields('f', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'distribution' files.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_distribution', 'd')
        ->fields('d', ['file_path'])
        ->isNotNull('d.file_path')
        ->execute()
        ->fetchCol()
    );

    // Add 'document' files.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_document_file', 'df')
        ->fields('df', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'documentation' files.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_documentation_file', 'df')
        ->fields('df', ['path'])
        ->execute()
        ->fetchCol()
    );

    // Add 'event' logos.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_event', 'e')
        ->fields('e', ['file_path'])
        ->isNotNull('e.file_path')
        ->condition('e.file_path', '', '<>')
        ->execute()
        ->fetchCol()
    );

    // Add 'solution' logos.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_solution', 's')
        ->fields('s', ['logo'])
        ->isNotNull('s.logo')
        ->condition('s.logo', 'sites/default/files/%', 'LIKE')
        ->execute()
        ->fetchCol()
    );

    // Add 'user' photos.
    $files = array_merge(
      $files,
      $this->legacyDb->select('d8_user', 'u')
        ->fields('u', ['photo_path'])
        ->isNotNull('u.photo_path')
        ->condition('u.photo_path', '', '<>')
        ->execute()
        ->fetchCol()
    );

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = $this->container->get('file_system');

    foreach ($files as $file) {
      $path = $base_dir . DIRECTORY_SEPARATOR . pathinfo($file, PATHINFO_DIRNAME);
      $file_name = pathinfo($file, PATHINFO_BASENAME);
      if (!is_dir($path)) {
        $file_system->mkdir($path, NULL, TRUE);
      }
      $file_path = $path . DIRECTORY_SEPARATOR . $file_name;
      if (!file_exists($file_path)) {
        // Create a '0 size' file.
        touch($path . DIRECTORY_SEPARATOR . $file_name);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Rollback migrations to cleanup RDF data.
    foreach ($this->manager->createInstances(static::$rdfMigrations) as $id => $migration) {
      try {
        (new MigrateExecutable($migration, $this))->rollback();
      }
      catch (\Exception $e) {
        $class = get_class($e);
        $this->display("$class: {$e->getMessage()} ({$e->getFile()}, {$e->getLine()})", 'error');
      }
    }

    Database::removeConnection('migrate');
    $this->db = NULL;
    $this->legacyDb = NULL;
    $this->manager = NULL;
    unset($this->messages);
    parent::tearDown();
  }

  /**
   * Asserts that a message has been logged.
   *
   * @param string $migration_id
   *   The migration.
   * @param string $message
   *   The message to be checked.
   * @param string $operator
   *   (optional) The operator to be used for comparision. Defaults to '='.
   */
  protected function assertMessage($migration_id, $message, $operator = '=') {
    $table = "migrate_message_{$migration_id}";
    $found = (bool) $this->db->select($table, 'm')
      ->fields('m')
      ->condition('m.message', $message, $operator)
      ->execute()
      ->fetchAll();
    $this->assertTrue($found);
  }

  /**
   * Asserts total number of items migrated.
   *
   * @param string $migration_id
   *   The migration.
   * @param int $count
   *   The count.
   */
  protected function assertTotalCount($migration_id, $count) {
    $this->countHelper($migration_id, $count);
  }

  /**
   * Asserts that number of items successfully migrated.
   *
   * @param string $migration_id
   *   The migration.
   * @param int $count
   *   The count.
   */
  protected function assertSuccessCount($migration_id, $count) {
    $this->countHelper($migration_id, $count, MigrateIdMapInterface::STATUS_IMPORTED);
  }

  /**
   * Asserts number of migrated items with a given status.
   *
   * @param string $migration_id
   *   The migration.
   * @param int $count
   *   The count.
   * @param int $status
   *   (optional) The migration status. If missed all the items are counted.
   */
  protected function countHelper($migration_id, $count, $status = NULL) {
    $table = "migrate_map_{$migration_id}";
    $query = $this->db->select($table)->fields($table);
    if ($status !== NULL) {
      $query->condition('source_row_status', $status);
    }

    $actual_count = $query
      ->countQuery()
      ->execute()
      ->fetchField();

    $this->assertEquals($count, $actual_count);
  }

  /**
   * Asserts that an entity reference field refers.
   *
   * @param string[] $expected_labels
   *   The expected entity labels.
   * @param \Drupal\Core\Field\EntityReferenceFieldItemListInterface $field
   *   The entity field.
   *
   * @throws \InvalidArgumentException
   *   When the entity type lacks a label key.
   */
  protected function assertReferences(array $expected_labels, EntityReferenceFieldItemListInterface $field) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $target_entity_type_id = $field->getFieldDefinition()->getSetting('target_type');
    $target_entity_type = $entity_type_manager->getDefinition($target_entity_type_id);
    if (!$target_entity_type->hasKey('label')) {
      throw new \InvalidArgumentException("Entity type '$target_entity_type_id' doesn't have a label key.");
    }

    // Build a list of referenced entities labels.
    $labels = array_map(function (EntityInterface $entity) {
      return $entity->label();
    }, $field->referencedEntities());

    sort($expected_labels);
    sort($labels);

    $this->assertSame($expected_labels, $labels);
  }

  /**
   * Asserts that an entity has the expected list of keywords.
   *
   * @param string[] $expected_keywords
   *   A list of expected keywords.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity.
   * @param string $field_name
   *   (optional) If passed, allows a field other than 'field_keywords'.
   *
   * @throws \InvalidArgumentException
   *   When the entity is missing the field.
   */
  protected function assertKeywords(array $expected_keywords, ContentEntityInterface $entity, $field_name = 'field_keywords') {
    if (!$entity->hasField($field_name)) {
      throw new \InvalidArgumentException("{$entity->getEntityType()->getLabel()} entity doesn't have a '$field_name' field.");
    }

    $keywords = array_map(function (array $item) {
      return $item['value'];
    }, $entity->get($field_name)->getValue());

    sort($expected_keywords);
    sort($keywords);

    $this->assertSame($expected_keywords, $keywords);
  }

  /**
   * List of Europe countries.
   *
   * Used to test spatial coverage from Drupal 6 'Europe' term.
   *
   * @var string[]
   */
  protected static $europeCountries = [
    'Albania',
    'Andorra',
    'Austria',
    'Belarus',
    'Belgium',
    'Bosnia and Herzegovina',
    'Bulgaria',
    'Croatia',
    'Cyprus',
    'Czech Republic',
    'Denmark',
    'Estonia',
    'European Union',
    'Faroes',
    'Finland',
    'France',
    'Former Yugoslav Republic of Macedonia',
    'Germany',
    'Gibraltar',
    'Gilbert and Ellice Islands',
    'Greece',
    'Guernsey',
    'Hungary',
    'Iceland',
    'Ireland',
    'Italy',
    'Jersey',
    'Kosovo',
    'Latvia',
    'Liechtenstein',
    'Lithuania',
    'Luxembourg',
    'Malta',
    'Moldova',
    'Monaco',
    'Montenegro',
    'Netherlands',
    'Norway',
    'Poland',
    'Portugal',
    'Romania',
    'Russia',
    'San Marino',
    'Serbia',
    'Slovakia',
    'Slovenia',
    'Spain',
    'Sweden',
    'Switzerland',
    'Ukraine',
    'United Kingdom',
    'Vatican City',
  ];

  /**
   * Migrations that are creating RDF objects.
   *
   * @var string[]
   */
  protected static $rdfMigrations = [
    'collection',
    'contact',
    'distribution',
    'licence',
    'owner',
    'policy_domain',
    'release',
    'solution',
  ];

}
