<?php

declare(strict_types = 1);

namespace Drupal\joinup_core\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\Core\Database\Connection;
use Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface;
use Drush\Commands\DrushCommands;
use Drush\Drupal\Commands\sql\SanitizePluginInterface;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Drush commands declared by Joinup project.
 */
class JoinupCoreDataSanitizationCommands extends DrushCommands implements SanitizePluginInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $db;

  /**
   * The SPARQL connection.
   *
   * @var \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface
   */
  protected $sparql;

  /**
   * Constructs a new Drush commands class instance.
   *
   * @param \Drupal\Core\Database\Connection $db
   *   The database connection.
   * @param \Drupal\sparql_entity_storage\Driver\Database\sparql\ConnectionInterface $sparql
   *   The SPARQL connection.
   */
  public function __construct(Connection $db, ConnectionInterface $sparql) {
    parent::__construct();
    $this->db = $db;
    $this->sparql = $sparql;
  }

  /**
   * Sanitize Joinup specific private data.
   *
   * @param $result
   *   Exit code from the main operation for sql-sanitize.
   * @param CommandData $commandData
   *   Information about the current request.
   *
   * @hook post-command sql-sanitize
   */
  public function sanitize($result, CommandData $commandData): void {
    $this->sanitizeUserSocialMedia();
    // We don't use the Drush core user fields sanitization as that is replacing
    // the values with the longest possible value, breaking the page layout.
    $this->sanitizeUserFields();
    $this->sanitizeAuthMapTable();
    $this->sanitizeContactInfo();
  }

  /**
   * @hook on-event sql-sanitize-confirms
   *
   * @inheritdoc
   */
  public function messages(&$messages, InputInterface $input): void {
    $messages[] = dt('Sanitize user social media data.');
    $messages[] = dt('Sanitize user fields (custom).');
    $messages[] = dt('Sanitize EU Login account mappings.');
    $messages[] = dt('Sanitize contact information emails.');
  }

  /**
   * Sanitizes the user social media data.
   */
  protected function sanitizeUserSocialMedia(): void {
    $this->db->truncate('user__field_social_media')->execute();
    $this->logger()->success(dt('user__field_social_media table sanitized.'));
  }

  /**
   * Sanitizes the user fields: first & family name, organisation, title.
   */
  protected function sanitizeUserFields(): void {
    $fields = [
      'field_user_organisation',
      'field_user_family_name',
      'field_user_first_name',
      'field_user_business_title',
    ];
    foreach ($fields as $field) {
      $table = "user__{$field}";
      $column = "{$field}_value";
      $this->db->update($table)
        ->expression($column, "CONCAT(SUBSTRING({$column}, 1, 1), LEFT(SHA1(UUID()), FLOOR(RAND()*(15-7+1)+7)))")
        ->execute();
      $this->logger()->success(dt('!table table sanitized (custom).', [
        '!table' => $table,
      ]));
    }
  }

  /**
   * Sanitizes the contact information emails.
   */
  protected function sanitizeAuthMapTable(): void {
    $this->db->update('authmap')
      ->expression('authname', 'CONCAT(SUBSTRING(authname, 1, 1), LEFT(SHA1(UUID()), 7))')
      ->execute();
    $this->logger()->success(dt('authmap table sanitized.'));
  }

  /**
   * Sanitizes the contact information emails.
   */
  protected function sanitizeContactInfo(): void {
    $this->sparql->query('WITH <http://joinup.eu/contact-information/published>
      DELETE { ?s <http://www.w3.org/2006/vcard/ns#hasEmail> ?o }
      INSERT {
        ?s <http://www.w3.org/2006/vcard/ns#hasEmail> "foo@localhost.localdomain"
      }
      WHERE {
        ?s a <http://www.w3.org/2006/vcard/ns#Kind> .
        ?s <http://www.w3.org/2006/vcard/ns#hasEmail> ?o .
      }');
    $this->logger()->success(dt('Contact information emails sanitized.'));
  }

}
