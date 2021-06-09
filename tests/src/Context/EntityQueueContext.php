<?php

declare(strict_types = 1);

namespace Drupal\joinup\Context;

use Behat\Gherkin\Node\TableNode;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\DrupalExtension\Context\RawDrupalContext;
use Drupal\joinup\Traits\EntityTrait;

/**
 * Behat step definitions to interact with entity queues.
 *
 * In our domain specific language these are called "curated content listings".
 */
class EntityQueueContext extends RawDrupalContext {

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

}
