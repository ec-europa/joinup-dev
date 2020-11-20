<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_debug\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the joinup_debug content update dblog entries.
 */
class DbLogTest extends KernelTestBase {

  use ContentTypeCreationTrait {
    createContentType as drupalCreateContentType;
  }
  use StringTranslationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'dblog',
    'field',
    'filter',
    'joinup_debug',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
    $this->installSchema('system', 'sequences');
    $this->installSchema('dblog', 'watchdog');
    $this->installSchema('node', 'node_access');
    $this->drupalCreateContentType([
      'type' => 'page',
      'name' => 'Basic page',
      'display_submitted' => FALSE,
    ]);
  }

  /**
   * Tests the wrong revision update.
   */
  public function testDbLogRevisionUpdate() {
    /** @var \Drupal\node\NodeStorageInterface $node_storage */
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $watchdog_query = "SELECT COUNT(wid) FROM {watchdog} WHERE type = 'joinup_debug'";

    /** @var \Drupal\node\NodeInterface $node */
    $node = $node_storage->create([
      'title' => $this->randomString(),
      'type' => 'page',
    ]);
    $node->save();
    $revision_id = $node->getRevisionId();

    $count = Database::getConnection()->query($watchdog_query)->fetchField();
    $this->assertEquals(0, $count, 'No entries should have been created due to the wrong revision being saved.');

    // Create a new version.
    $node->setNewRevision();
    $node->set('title', $this->randomString());
    $node->save();
    $new_revision_id = $node->getRevisionId();

    $this->assertNotEquals($revision_id, $new_revision_id);
    $count = Database::getConnection()->query($watchdog_query)->fetchField();
    $this->assertEquals(0, $count, 'No entries should have been created due to the wrong revision being saved.');

    // Update the previous revision.
    $initial_revision = $node_storage->loadRevision($revision_id);
    $initial_revision->set('title', $this->randomString());
    $initial_revision->save();

    $count = Database::getConnection()->query($watchdog_query)->fetchField();
    $this->assertEquals(2, $count, '2 Entries should have been created due to the wrong revision being saved.');
  }

}
