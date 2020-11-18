<?php

declare(strict_types = 1);

namespace Drupal\joinup_licence\Entity;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_bundle_class\JoinupBundleClassFieldAccessTrait;
use Drupal\joinup_licence\Plugin\Field\CompatibilityDocumentLicenceFieldItemList;

/**
 * Defines the compatibility document entity.
 *
 * These entities contain additional information about compatibility between
 * licences as described by licence compatibility rule plugins. The rule plugins
 * refer to the documents by their plugin ID which is unique for each rule and
 * corresponds to the numbered test cases in the original functionality
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
 *     "form" = {
 *       "edit" = "Drupal\joinup_licence\Form\CompatibilityDocumentForm",
 *     },
 *     "list_builder" = "Drupal\joinup_licence\CompatibilityDocumentListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *     "view_builder" = "Drupal\joinup_licence\CompatibilityDocumentViewBuilder",
 *   },
 *   base_table = "compatibility_document",
 *   admin_permission = "access compatibility document overview",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title"
 *   },
 *   links = {
 *     "edit-form" = "/admin/structure/compatibility-document/{compatibility_document}/edit",
 *     "collection" = "/admin/structure/compatibility-document"
 *   },
 *   field_ui_base_route = "entity.compatibility_document.collection"
 * )
 */
class CompatibilityDocument extends ContentEntityBase implements CompatibilityDocumentInterface {

  use JoinupBundleClassFieldAccessTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = [];

    $fields['id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('ID'))
      ->setReadOnly(TRUE)
      ->setSetting('max_length', 32)
      ->setSetting('is_ascii', TRUE);

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

    $fields['inbound_licence'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Use licence'))
      ->setDescription(t('The licence of the project that is being used as part of a new project.'))
      ->setComputed(TRUE)
      ->setClass(CompatibilityDocumentLicenceFieldItemList::class);

    $fields['outbound_licence'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Redistribute as licence'))
      ->setDescription(t('The licence under which the new project will be distributed.'))
      ->setComputed(TRUE)
      ->setClass(CompatibilityDocumentLicenceFieldItemList::class);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
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
    $plugin_ids = array_keys($plugin_manager->getDefinitions());

    $entity_ids = $storage->getQuery()->execute();
    $missing_entity_ids = array_diff($plugin_ids, $entity_ids);

    foreach ($missing_entity_ids as $entity_id) {
      $storage->create([
        'id' => $entity_id,
        'description' => 'Compatibility document comparing @inbound-licence with @outbound-licence.',
      ])->save();
    }

    // Some plugins might have been removed from the codebase or third-party
    // modules, shipping plugins of this type, might have been uninstalled.
    if ($removed_entity_ids = array_diff($entity_ids, $plugin_ids)) {
      $storage->delete($storage->loadMultiple($removed_entity_ids));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setUseLicence(LicenceInterface $licence): CompatibilityDocumentInterface {
    $this->set('inbound_licence', $licence);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setRedistributeAsLicence(LicenceInterface $licence): CompatibilityDocumentInterface {
    $this->set('outbound_licence', $licence);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getUseLicence(): ?LicenceInterface {
    return $this->getLicence('inbound_licence');
  }

  /**
   * {@inheritdoc}
   */
  public function getRedistributeAsLicence(): ?LicenceInterface {
    return $this->getLicence('outbound_licence');
  }

  /**
   * Returns the licence that is referenced by the field with the given name.
   *
   * @param string $field_name
   *   The name of the entity reference field.
   *
   * @return \Drupal\joinup_licence\Entity\LicenceInterface|null
   *   The referenced licence, or NULL if this field does not reference one.
   */
  protected function getLicence(string $field_name): ?LicenceInterface {
    $entity = $this->getFirstReferencedEntity($field_name);
    if ($entity instanceof LicenceInterface) {
      return $entity;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): FormattableMarkup {
    $description = $this->getMainPropertyValue('description') ?? '';
    $inbound_licence = $this->getUseLicence();
    $inbound_licence_id = $inbound_licence ? $inbound_licence->getSpdxLicenceId() ?? $this->t('Unknown') : $this->t('Unknown');
    $outbound_licence = $this->getRedistributeAsLicence();
    $outbound_licence_id = $outbound_licence ? $outbound_licence->getSpdxLicenceId() ?? $this->t('Unknown') : $this->t('Unknown');

    return new FormattableMarkup($description, [
      '@inbound-licence' => $inbound_licence_id,
      '@outbound-licence' => $outbound_licence_id,
    ]);
  }

}
