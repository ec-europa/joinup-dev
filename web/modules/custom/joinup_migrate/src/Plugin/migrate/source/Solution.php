<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\FieldTranslationInterface;
use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Migrates solutions.
 *
 * @MigrateSource(
 *   id = "solution"
 * )
 */
class Solution extends JoinupSqlBase implements RedirectImportInterface, FieldTranslationInterface {

  use CountryTrait;
  use DefaultRdfRedirectTrait;
  use DocumentationTrait;
  use FieldTranslationTrait;
  use FileUrlFieldTrait;
  use KeywordsTrait;
  use StateTrait;
  use StatusTrait;

  /**
   * {@inheritdoc}
   */
  protected $reservedUriTables = ['collection'];

  /**
   * {@inheritdoc}
   */
  protected $uriProperties = ['uri', 'landing_page', 'metrics_page'];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 's',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'nid' => $this->t('ID'),
      'uri' => $this->t('URI'),
      'title' => $this->t('Title'),
      'created_time' => $this->t('Creation date'),
      'body' => $this->t('Description'),
      'changed_time' => $this->t('Last changed date'),
      'owner' => $this->t('Owners'),
      'owner_name' => $this->t('Text owner name'),
      'owner_type' => $this->t('Text owner type'),
      'keywords' => $this->t('Keywords'),
      'landing_page' => $this->t('Landing page'),
      'logo' => $this->t('Logo'),
      'metrics_page' => $this->t('Metrics page'),
      'policy2' => $this->t('Policy domain'),
      'related' => $this->t('Related solutions'),
      'country' => $this->t('Country'),
      'status' => $this->t('Status'),
      'contact' => $this->t('Contact info'),
      'contact_email' => $this->t('Contact E-mail'),
      'distribution' => $this->t('Distribution'),
      'documentation' => $this->t('Documentation'),
      'state' => $this->t('State'),
      'item_state' => $this->t('Item state'),
      'i18n' => $this->t('Field translations'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_solution', 's')->fields('s', [
      'nid',
      'vid',
      'type',
      'title',
      'uri',
      'created_time',
      'changed_time',
      'body',
      'policy2',
      'landing_page',
      'metrics_page',
      'state',
      'item_state',
      'contact_email',
      'owner_name',
      'owner_type',
      'logo_id',
      'banner',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Keywords.
    $this->setKeywords($row, 'keywords', $nid, $vid);

    // Resolve documentation.
    list($file_source_id_values, $urls) = $this->getAssetReleaseDocumentation($vid);
    $this->setFileUrlTargetId($row, 'documentation', $file_source_id_values, 'file:documentation', $urls, JoinupSqlBase::FILE_URL_MODE_MULTIPLE);

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    // Owners.
    $owner = $this->select('d8_owner_solution', 'o')
      ->fields('o', ['nid'])
      ->condition('o.solution', $nid)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('owner', $owner);

    // Contacts.
    $contact = $this->select('d8_contact_solution', 'c')
      ->fields('c', ['nid'])
      ->condition('c.solution', $nid)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('contact', $contact);

    // Distributions.
    $query = $this->select('content_field_asset_distribution', 'd')
      ->fields('n', ['nid'])
      ->condition('d.vid', $vid);
    $query->join('node', 'n', 'd.field_asset_distribution_nid = n.nid');
    $distributions = $query->execute()->fetchCol();
    $row->setSourceProperty('distribution', $distributions);

    // Status.
    $this->setStatus($vid, $row);

    // State.
    $this->setState($row);

    // Only 'asset_release' type provides field translation.
    if ($row->getSourceProperty('type') === 'asset_release') {
      $this->setFieldTranslations($row);
    }

    return parent::prepareRow($row);
  }

  /**
   * Gets the (D6) 'asset_release' documentation given its node revision ID.
   *
   * @param int $vid
   *   The (D6) 'asset_release' node revision ID.
   *
   * @return array[]
   *   An indexed array where the first item is a list of file IDs, each one
   *   represented as source IDs (example [['fid' => 123, 'fid' => 987]]) and
   *   the second item is a simple array of URLs.
   */
  protected function getAssetReleaseDocumentation($vid) {
    $items = $this->select('d8_file_documentation', 'd')->fields('d')
      ->condition('d.vid', $vid)
      ->execute()
      ->fetchAll();

    $return = [[], []];
    foreach ($items as $item) {
      if (!empty($item['fid'])) {
        $return[0][] = ['fid' => $item['fid']];
      }
      if (!empty($item['url'])) {
        $return[1][] = $item['url'];
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslatableFields() {
    return [
      'label' => [
        'table' => 'content_field_asset_name',
        'field' => 'field_asset_name_value',
        'sub_field' => 'field_language_textfield_name',
      ],
      'field_is_description' => [
        'table' => 'content_field_asset_description',
        'field' => 'field_asset_description_value',
        'sub_field' => 'field_language_textarea_name',
      ],
    ];
  }

}
