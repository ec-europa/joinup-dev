<?php
namespace Drupal\joinup_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * List file field instances.
 *
 * @package Drupal\joinup_core\Controller
 */
class FileFields extends ControllerBase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new class instance.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity manager.
   */
  public function __construct(EntityFieldManagerInterface $entity_field_manager) {
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_field.manager')
    );
  }

  /**
   * Generates the list.
   */
  public function listing() {
    $fields_structure = [];
    $fields_structure = array_merge_recursive($fields_structure, $this->entityFieldManager->getFieldMapByFieldType('file'));
    $fields_structure = array_merge_recursive($fields_structure, $this->entityFieldManager->getFieldMapByFieldType('file_url'));
    $fields_structure = array_merge_recursive($fields_structure, $this->entityFieldManager->getFieldMapByFieldType('image'));

    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->t('File fields'),
      '#rows' => [],
      '#empty' => $this->t('There are no file fields yet.'),
    ];
    foreach ($fields_structure as $entity_type => $fields) {
      foreach ($fields as $field_name => $field) {
        foreach ($field['bundles'] as $bundle) {
          $build['table']['#rows'][$field_name] = $this->buildRow($entity_type, $bundle, $field_name);
        }
      }
    }
    return $build;
  }

  /**
   * Builds one row of the file field definition table.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $field_name
   *   The field name.
   *
   * @return array
   *   The render array of one row.
   */
  protected function buildRow($entity_type, $bundle, $field_name) {
    $info = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
    /** @var \Drupal\field\Entity\FieldConfig $field_info */
    $field_info = $info[$field_name];
    $dir = $field_info->getSetting('file_directory');
    $extensions = $field_info->getSetting('file_extensions');
    return [
      'entity_type' => $entity_type,
      'bundle' => $bundle,
      'field_name' => $field_info->label() . " ($field_name)",
      'storage_path' => $dir,
      'allowed_extensions' => $extensions,
    ];
  }

  /**
   * Builds the table header.
   *
   * @return array
   *   Render array of table header.
   */
  protected function buildHeader() {
    $header = [
      'entity_type' => [
        'data' => $this->t('Entity type'),
        'field' => 'entity_type',
      ],
      'bundle' => [
        'data' => $this->t('Bundle'),
        'field' => 'bundle',
      ],
      'field_name' => [
        'data' => $this->t('Field name'),
        'field' => 'field_name',
      ],
      'storage_path' => [
        'data' => $this->t('Storage directory'),
        'field' => 'field_name',
      ],
      'allowed_extensions' => [
        'data' => $this->t('Allowed extensions'),
        'field' => 'allowed_extensions',
      ],
    ];
    return $header;
  }

}
