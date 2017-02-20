<?php

namespace Drupal\joinup_migrate\Plugin\migrate\source;

use Drupal\migrate\Row;

/**
 * Migrates releases.
 *
 * @MigrateSource(
 *   id = "release"
 * )
 */
class Release extends JoinupSqlBase {

  use CountryTrait;
  use RdfFileFieldTrait;

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
      'created_time',
      'changed_time',
      'uri',
      'solution',
      'language',
      'version_notes',
      'version_number',
      'docs_path',
      'docs_url',
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

    // Destroy self lookup URIs.
    $uri = $row->getSourceProperty('uri');
    if ($uri == "https://joinup.ec.europa.eu/node/$nid") {
      $row->setSourceProperty('uri', NULL);
    }
    else {
      $alias = $this->select('url_alias', 'a')
        ->fields('a', ['dst'])
        ->condition('a.src', "node/$nid")
        ->orderBy('a.pid', 'DESC')
        ->range(0, 1)
        ->execute()
        ->fetchField();
      if ($alias && ($uri === $alias)) {
        $row->setSourceProperty('uri', NULL);
      }
    }

    // Assure a created date.
    if (!$row->getSourceProperty('created_time')) {
      $row->setSourceProperty('created_time', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }
    // Assure a changed date.
    if (!$row->getSourceProperty('changed_time')) {
      $row->setSourceProperty('changed_time', date('Y-m-d\TH:i:s', REQUEST_TIME));
    }

    // Keywords.
    $query = $this->select('term_node', 'tn');
    $query->join('term_data', 'td', 'tn.tid = td.tid');
    $keywords = $query
      ->fields('td', ['name'])
      ->condition('tn.nid', $nid)
      ->condition('tn.vid', $vid)
      // The keywords vocabulary vid is 28.
      ->condition('td.vid', 28)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('keywords', array_unique($keywords));

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
    $this->setRdfFileTargetId($row, 'documentation', ['nid' => $nid], 'docs_path', 'documentation_file', 'docs_url');

    return parent::prepareRow($row);
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
