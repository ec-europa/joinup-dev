<?php

namespace Drupal\Tests\joinup_migrate\Functional;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\rdf_entity\Traits\RdfDatabaseConnectionTrait;

/**
 * Tests Joinup migration.
 *
 * @group joinup
 */
class JoinupMigrateTest extends BrowserTestBase implements MigrateMessageInterface {

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

    parent::setUp();

    // Setup the connections to main and SPARQL backends.
    $this->db = Database::getConnection();
    $this->setUpSparqlForBrowser();

    // Prepare migration environment.
    $this->setUpMigration();

    // Run test migrations.
    $this->runMigrations();
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

    // Assertions for each migrations are defined under assert/ directory.
    foreach (file_scan_directory(__DIR__ . '/assert', '|\.php$|') as $file) {
      require __DIR__ . '/assert/' . $file->filename;
    }
  }

  /**
   * Runs all available migrations.
   */
  protected function runMigrations() {
    foreach ($this->manager->createInstances([]) as $id => $migration) {
      // Force running the migration, even the prior migrations were incomplete.
      $migration->set('requirements', []);
      try {
        (new MigrateExecutable($migration, $this))->import();
      }
      catch (\Exception $e) {
        $class = get_class($e);
        $this->display("$class: {$e->getMessage()} ({$e->getFile()}, {$e->getLine()})", 'error');
      }
    }
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
    $this->setUpLegacyDb();

    // Set the legacy site webroot.
    if (!$legacy_webroot = getenv('SIMPLETEST_LEGACY_WEBROOT')) {
      throw new \Exception('The legacy site webroot is not set. You must provide a SIMPLETEST_LEGACY_WEBROOT environment variable.');
    }

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
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
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
   * Returns an entity by its label.
   *
   * Being used for testing, this method assumes that, within an entity type,
   * all entities have unique labels.
   *
   * @param string $entity_type_id
   *   The entity type ID.
   * @param string $label
   *   The entity label.
   *
   * @return \Drupal\Core\Entity\ContentEntityInterface
   *   The content entity.
   *
   * @throws \InvalidArgumentException
   *   When the entity type lacks a label key.
   * @throws \Exception
   *   When the entity with the specified label was not found.
   */
  protected function loadEntityByLabel($entity_type_id, $label) {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager */
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    if (!$entity_type->hasKey('label')) {
      throw new \InvalidArgumentException("Entity type '$entity_type_id' doesn't have a label key.");
    }

    $label_key = $entity_type->getKey('label');
    $storage = $entity_type_manager->getStorage($entity_type_id);

    if (!$entities = $storage->loadByProperties([$label_key => $label])) {
      throw new \Exception("No $entity_type_id entity with $label_key '$label' was found.");
    }

    return reset($entities);
  }

}
