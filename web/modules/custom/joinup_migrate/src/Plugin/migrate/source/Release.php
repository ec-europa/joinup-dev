<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\joinup_migrate\FieldTranslationInterface;
use Drupal\joinup_migrate\RedirectImportInterface;
use Drupal\migrate\Row;

/**
 * Migrates releases.
 *
 * @MigrateSource(
 *   id = "release"
 * )
 */
class Release extends JoinupSqlBase implements RedirectImportInterface, FieldTranslationInterface {

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
  protected $reservedUriTables = ['collection', 'solution'];

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => [
        'type' => 'integer',
        'alias' => 'r',
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
      'body' => $this->t('Description'),
      'created_time' => $this->t('Creation date'),
      'distribution' => $this->t('Distribution'),
      'solution' => $this->t('Solution ID'),
      'keywords' => $this->t('Keywords'),
      'language' => $this->t('language'),
      'changed_time' => $this->t('Last changed date'),
      'version_notes' => $this->t('Version notes'),
      'version_number' => $this->t('Version number'),
      'country' => $this->t('Country'),
      'status' => $this->t('Status'),
      'documentation' => $this->t('Documentation'),
      'state' => $this->t('State'),
      'item_state' => $this->t('Item state'),
    ] + parent::fields();
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('d8_release', 'r')->fields('r', [
      'nid',
      'vid',
      'title',
      'body',
      'created_time',
      'changed_time',
      'uri',
      'solution',
      'language',
      'version_notes',
      'version_number',
      'state',
      'item_state',
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

    // Distributions.
    $query = $this->select('content_field_asset_distribution', 'd')
      ->fields('n', ['nid'])
      ->condition('d.vid', $vid);
    $query->join('node', 'n', 'd.field_asset_distribution_nid = n.nid');
    $distributions = $query->execute()->fetchCol();
    $row->setSourceProperty('distribution', $distributions);

    // Language.
    $row->setSourceProperty('language', $this->convertLanguage($row->getSourceProperty('language')));

    // Release notes.
    $version_notes = NULL;
    $notes = unserialize($row->getSourceProperty('version_notes'));
    if ($notes && isset($notes['field_language_textarea_name'][0]['value'])) {
      $version_notes = $notes['field_language_textarea_name'][0]['value'];
    }
    $row->setSourceProperty('version_notes', $version_notes);

    // Release number.
    $version_number = NULL;
    $notes = unserialize($row->getSourceProperty('version_number'));
    if ($notes && isset($notes['field_language_textfield_name'][0]['value'])) {
      $version_number = $notes['field_language_textfield_name'][0]['value'];
    }
    $row->setSourceProperty('version_number', $version_number);

    // Spatial coverage.
    $row->setSourceProperty('country', $this->getCountries([$vid]));

    // Resolve documentation.
    list($file_source_id_values, $urls) = $this->getAssetReleaseDocumentation($vid);
    $this->setFileUrlTargetId($row, 'documentation', $file_source_id_values, 'file:documentation', $urls, JoinupSqlBase::FILE_URL_MODE_MULTIPLE);

    // Status.
    $this->setStatus($vid, $row);

    // State.
    if ($row->getSourceProperty('item_state') === 'proposed') {
      // Releases have no 'proposed' state (why?). What if 'proposed' is piped?
      $row->setSourceProperty('item_state', 'draft');
    }
    $row->setSourceProperty('type', 'asset_release');
    $this->setState($row);

    // Set field translations.
    $this->setFieldTranslations($row);

    return parent::prepareRow($row);
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
      'field_isr_description' => [
        'table' => 'content_field_asset_description',
        'field' => 'field_asset_description_value',
        'sub_field' => 'field_language_textarea_name',
      ],
    ];
  }

  /**
   * Converts a two letter language code to 'language' vocabulary term ID.
   *
   * @param string $langcode
   *   The two letter language code.
   *
   * @return string|null
   *   Language as 'language' vocabulary term ID.
   */
  protected function convertLanguage($langcode) {
    $langcode = strtolower($langcode);
    if (!isset(static::$langcodeMap[$langcode])) {
      return NULL;
    }
    return 'http://publications.europa.eu/resource/authority/language/' . strtoupper(static::$langcodeMap[$langcode]);
  }

  /**
   * Language code conversion table.
   *
   * @var string[]
   */
  protected static $langcodeMap = [
    'aa' => 'aar',
    'ab' => 'abk',
    'ae' => 'ave',
    'af' => 'afr',
    'am' => 'amh',
    'an' => 'arg',
    'ar' => 'ara',
    'as' => 'asm',
    'av' => 'ava',
    'ay' => 'aym',
    'az' => 'aze',
    'ba' => 'bak',
    'be' => 'bel',
    'bg' => 'bul',
    'bh' => 'bih',
    'bi' => 'bis',
    'bm' => 'bam',
    'bn' => 'ben',
    'bo' => 'bod',
    'br' => 'bre',
    'bs' => 'bos',
    'ca' => 'cat',
    'ce' => 'che',
    'ch' => 'cha',
    'co' => 'cos',
    'cr' => 'cre',
    'cs' => 'ces',
    'cu' => 'chu',
    'cv' => 'chv',
    'cy' => 'cym',
    'da' => 'dan',
    'de' => 'deu',
    'dv' => 'div',
    'dz' => 'dzo',
    'ee' => 'ewe',
    'el' => 'ell',
    'en' => 'eng',
    'eo' => 'epo',
    'es' => 'spa',
    'et' => 'est',
    'eu' => 'eus',
    'fa' => 'fas',
    'ff' => 'ful',
    'fi' => 'fin',
    'fj' => 'fij',
    'fo' => 'fao',
    'fr' => 'fra',
    'fy' => 'fry',
    'ga' => 'gle',
    'gd' => 'gla',
    'gl' => 'glg',
    'gn' => 'grn',
    'gu' => 'guj',
    'gv' => 'glv',
    'ha' => 'hau',
    'he' => 'heb',
    'hi' => 'hin',
    'ho' => 'hmo',
    'hr' => 'hrv',
    'ht' => 'hat',
    'hu' => 'hun',
    'hy' => 'hye',
    'hz' => 'her',
    'ia' => 'ina',
    'id' => 'ind',
    'ie' => 'ile',
    'ig' => 'ibo',
    'ii' => 'iii',
    'ik' => 'ipk',
    'io' => 'ido',
    'is' => 'isl',
    'it' => 'ita',
    'iu' => 'iku',
    'ja' => 'jpn',
    'jv' => 'jav',
    'ka' => 'kat',
    'kg' => 'kon',
    'ki' => 'kik',
    'kj' => 'kua',
    'kk' => 'kaz',
    'kl' => 'kal',
    'km' => 'khm',
    'kn' => 'kan',
    'ko' => 'kor',
    'kr' => 'kau',
    'ks' => 'kas',
    'ku' => 'kur',
    'kv' => 'kom',
    'kw' => 'cor',
    'ky' => 'kir',
    'la' => 'lat',
    'lb' => 'ltz',
    'lg' => 'lug',
    'li' => 'lim',
    'ln' => 'lin',
    'lo' => 'lao',
    'lt' => 'lit',
    'lu' => 'lub',
    'lv' => 'lav',
    'mg' => 'mlg',
    'mh' => 'mah',
    'mi' => 'mri',
    'mk' => 'mkd',
    'ml' => 'mal',
    'mn' => 'mon',
    'mr' => 'mar',
    'ms' => 'msa',
    'mt' => 'mlt',
    'my' => 'mya',
    'na' => 'nau',
    'nb' => 'nob',
    'nd' => 'nde',
    'ne' => 'nep',
    'ng' => 'ndo',
    'nl' => 'nld',
    'nn' => 'nno',
    'no' => 'nor',
    'nr' => 'nbl',
    'nv' => 'nav',
    'ny' => 'nya',
    'oc' => 'oci',
    'oj' => 'oji',
    'om' => 'orm',
    'or' => 'ori',
    'os' => 'oss',
    'pa' => 'pan',
    'pi' => 'pli',
    'pl' => 'pol',
    'ps' => 'pus',
    'pt' => 'por',
    'qu' => 'que',
    'rm' => 'roh',
    'rn' => 'run',
    'ro' => 'ron',
    'ru' => 'rus',
    'rw' => 'kin',
    'sa' => 'san',
    'sc' => 'srd',
    'sd' => 'snd',
    'se' => 'sme',
    'sg' => 'sag',
    'si' => 'sin',
    'sk' => 'slk',
    'sl' => 'slv',
    'sm' => 'smo',
    'sn' => 'sna',
    'so' => 'som',
    'sq' => 'sqi',
    'sr' => 'srp',
    'ss' => 'ssw',
    'st' => 'sot',
    'su' => 'sun',
    'sv' => 'swe',
    'sw' => 'swa',
    'ta' => 'tam',
    'te' => 'tel',
    'tg' => 'tgk',
    'th' => 'tha',
    'ti' => 'tir',
    'tk' => 'tuk',
    'tl' => 'tgl',
    'tn' => 'tsn',
    'to' => 'ton',
    'tr' => 'tur',
    'ts' => 'tso',
    'tt' => 'tat',
    'tw' => 'twi',
    'ty' => 'tah',
    'ug' => 'uig',
    'uk' => 'ukr',
    'ur' => 'urd',
    'uz' => 'uzb',
    've' => 'ven',
    'vi' => 'vie',
    'vo' => 'vol',
    'wa' => 'wln',
    'wo' => 'wol',
    'xh' => 'xho',
    'yi' => 'yid',
    'yo' => 'yor',
    'za' => 'zha',
    'zh' => 'zho',
    'zu' => 'zul',
  ];

}
