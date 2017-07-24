<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\Core\Database\Database;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\rdf_entity\Entity\Rdf;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A that runs after 'og_user_role_solution' migration.
 */
class SolutionOwnerCheckSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::POST_IMPORT => 'logSolutionWithoutOwner'];
  }

  /**
   * Logs all solutions without an owner.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The event object.
   */
  public function logSolutionWithoutOwner(MigrateImportEvent $event) {
    $migration = $event->getMigration();
    if ($migration->id() !== 'og_user_role_solution') {
      return;
    }

    $db = Database::getConnection();
    /** @var \Drupal\Core\Database\Query\SelectInterface $query */
    $query = $db->select('migrate_map_solution', 's')
      ->fields('s', ['destid1', 'sourceid1'])
      ->fields('ogm', ['id'])
      ->isNotNull('s.destid1');
    $query->leftJoin('og_membership', 'ogm', 's.destid1 = ogm.entity_id');

    if ($rows = $query->execute()->fetchAll()) {
      $data = [];
      foreach ($rows as $row) {
        if (!array_key_exists($row->destid1, $data)) {
          $data[$row->destid1] = [];
        }
        if (!array_key_exists('ids', $data[$row->destid1])) {
          $data[$row->destid1]['ids'] = [];
          $data[$row->destid1]['nid'] = (int) $row->sourceid1;
        }
        if ($row->id) {
          $data[$row->destid1]['ids'][] = (int) $row->id;
        }
      }

      /** @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager */
      $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
      /** @var \Drupal\migrate\Plugin\MigrationInterface $solution_migration */
      $solution_migration = $migration_plugin_manager->createInstance('solution');
      $id_map = $solution_migration->getIdMap();

      foreach ($data as $id => $row) {
        if (empty($row['ids'])) {
          $missing_owner = TRUE;
        }
        else {
          $missing_owner = !$db->query("SELECT 1 FROM {og_membership__roles} WHERE entity_id IN(:ids[]) AND roles_target_id = 'rdf_entity-solution-administrator'", [':ids[]' => $row['ids']])->fetchField();
        }
        if ($missing_owner) {
          $solution = Rdf::load($id);
          $id_map->saveMessage(['nid' => $row['nid']], "Solution '{$solution->label()}' (source nid {$row['nid']}): The solution owner is missed or the user was not migrated");
        }
      }
    }
  }

}
