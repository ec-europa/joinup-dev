<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\entityqueue\EntitySubqueueInterface;
use Drupal\joinup\Traits\AliasTranslatorTrait;
use Drupal\joinup\Traits\EntityTrait;

/**
 * Behat step definitions to interact with entity queues.
 *
 * In our domain specific language these are called "curated content listings".
 */
class EntityQueueContext extends RawDrupalContext {

  use AliasTranslatorTrait;
  use EntityTrait;

  /**
   * Sets the content of the given entity queue to what is listed in the table.
   *
   * Any pre-existing content in the entity queue will be replaced.
   *
   * Table format:
   *
   * @codingStandardsIgnoreStart
   * | type       |  label                   |
   * | collection |  Ants                    |
   * | content    |  Colony organisation 101 |
   * @codingStandardsIgnoreEnd
   *
   * The 'type' and 'label' columns are required.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The Behat table containing the content to include in the entity queue.
   * @param string $label
   *   The label of the entity queue to update.
   *
   * @Given the :label content listing contains:
   */
  public function setEntityQueueContent(TableNode $table, string $label): void {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = self::getEntityByLabel('entity_subqueue', $label);

    $entities = array_map(function (array $row): ContentEntityInterface {
      $entity_type_id = self::translateEntityTypeAlias($row['type']);
      $label = $row['label'];
      return self::getEntityByLabel($entity_type_id, $label);
    }, $table->getColumnsHash());

    $subqueue->get('items')->setValue($entities);
    $subqueue->save();
  }

  /**
   * Sets the fields of the given entity queue to what is listed in the table.
   *
   * Any pre-existing field values will be replaced and will not be restored.
   *
   * Check ::entityQueueFieldAliases() for the available fields.
   *
   * @param \Behat\Gherkin\Node\TableNode $table
   *   The Behat table containing the field data to set on the entity queue.
   * @param string $label
   *   The label of the entity queue to update.
   *
   * @Given the :label content listing has the following fields:
   */
  public function setEntityQueueFieldData(TableNode $table, string $label): void {
    /** @var \Drupal\entityqueue\EntitySubqueueInterface $subqueue */
    $subqueue = self::getEntityByLabel('entity_subqueue', $label);

    $values = $table->getRowsHash();
    $this->massageEntityQueueFieldValues($subqueue, $values);
    foreach ($values as $alias => $value) {
      $field_name = self::translateFieldNameAlias($alias, self::entityQueueFieldAliases());
      $subqueue->get($field_name)->setValue($value);
    }

    $subqueue->save();
  }

  /**
   * Prepares values for saving into an entityqueue.
   *
   * @param \Drupal\entityqueue\EntitySubqueueInterface $subqueue
   *   The entity subqueue for which to prepare the values.
   * @param array $values
   *   A reference to the values that need to be massaged.
   */
  protected static function massageEntityQueueFieldValues(EntitySubqueueInterface $subqueue, array &$values): void {
    switch ($subqueue->bundle()) {
      case 'highlighted_event':
        // If the external URL is passed, then set the 'Link to external page'
        // flag. This checkbox is intended for UX reasons, to make the edit form
        // for the highlighted event entity queue easier to understand.
        if (!empty($values['external url'])) {
          $values['link to external page'] = TRUE;
        }
        break;
    }
  }

  /**
   * Returns the mapping of human readable field aliases to field names.
   *
   * @return array
   *   The mapping.
   */
  protected static function entityQueueFieldAliases(): array {
    return [
      'header text' => 'header',
      'link text' => 'link_text',
      'external url' => 'external_url',
      'link to external page' => 'link_to_external_url',
    ];
  }

}
