<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the compatibility document entity.
 *
 * These entities contain additional information about compatibility between
 * licences as described by licence compatibility rule plugins. The rule plugins
 * refer to the documents by the `document_id` property which is unique for each
 * rule and corresponds to the numbered test cases in the original functionality
 * description. This document is not publicly available but people with access
 * to the private JIRA instance of the European Commission can find this
 * attached to the ticket ISAICP-6054, ref. 'SC237-D06.02 Specification'. People
 * without access can refer to the code of the rules plugins themselves for more
 * details.
 *
 * Since these compatibility documents have a 1-1 relationship with the rule
 * plugins there are no forms to add or delete entities. They are created on the
 * fly when the entity overview is accessed. The 1-1 relationship is maintained
 * using the entity ID.
 *
 * This information is stored in a content entity so it can be edited by
 * moderators on the production environment.
 *
 * @ContentEntityType(
 *   id = "compatibility_document",
 *   label = @Translation("Compatibility document"),
 *   label_collection = @Translation("Compatibility documents"),
 *   handlers = {
 *     "list_builder" = "Drupal\joinup_licence\CompatibilityDocumentListBuilder",
 *     "form" = {
 *       "edit" = "Drupal\joinup_licence\Form\CompatibilityDocumentForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "compatibility_document",
 *   admin_permission = "access compatibility document overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "edit-form" = "/admin/content/compatibility-document/{compatibility_document}/edit",
 *     "collection" = "/admin/content/compatibility-document"
 *   },
 * )
 */
class CompatibilityDocument extends ContentEntityBase implements CompatibilityDocumentInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('unsigned', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the compatibility document.'))
      ->setDisplayOptions('form', [
        'type' => 'text_textarea',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'text_default',
        'label' => 'above',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->id();
  }

  /**
   * {@inheritdoc}
   */
  public static function load($id): CompatibilityDocumentInterface {
    // The compatibility documents have a 1-1 relationship with the licence
    // compatibility plugins. It is the responsibility of the caller to ensure
    // that a correct ID is passed in. If the entity doesn't exist we throw an
    // exception, mercilessly.
    $entity = parent::load($id);
    if ($entity instanceof CompatibilityDocumentInterface) {
      return $entity;
    }
    throw new \InvalidArgumentException(sprintf('Requested invalid compatibility document %s', $id));
  }

  /**
   * {@inheritdoc}
   */
  public static function populate(): void {
    $storage = \Drupal::entityTypeManager()->getStorage('compatibility_document');

    /** @var \Drupal\joinup_licence\JoinupLicenceCompatibilityRulePluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.joinup_licence_compatibility_rule');
    $definitions = $plugin_manager->getDefinitions();
    $plugin_ids = array_filter(array_map(function (array $definition): string {
      return $definition['document_id'] ?? '';
    }, $definitions));
    $entity_ids = $storage->getQuery()->execute();
    $missing_entity_ids = array_diff($plugin_ids, $entity_ids);

    foreach ($missing_entity_ids as $entity_id) {
      $storage->create([
        'id' => $entity_id,
        'description' => 'Compatibility document comparing [licence-a] with [licence-b].',
      ])->save();
    }
  }

}
