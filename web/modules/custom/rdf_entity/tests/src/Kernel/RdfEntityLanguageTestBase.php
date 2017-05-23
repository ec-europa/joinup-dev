<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;

/**
 * Base class for language-aware entity tests.
 */
abstract class RdfEntityLanguageTestBase extends RdfKernelTestBase {

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The available language codes.
   *
   * @var array
   */
  protected $langcodes;

  /**
   * The test field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * The untranslatable test field name.
   *
   * @var string
   */
  protected $untranslatableFieldName;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'datetime',
    'rdf_entity_test',
  ];

  /**
   * Sets up basic conditions for a language test.
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installConfig(['rdf_entity_test']);
    $this->languageManager = $this->container->get('language_manager');
    $this->installConfig(['language']);
    // Enable translations for the rdf entity.
    $this->state->set('rdf_entity.translation', TRUE);

    // Create test languages.
    $this->langcodes = [];
    $language = ConfigurableLanguage::create([
      'id' => 'el',
      'label' => $this->randomString(),
    ]);
    $this->langcodes[] = $language->getId();
    $language->save();
    $language = ConfigurableLanguage::create([
      'id' => 'de',
      'label' => $this->randomString(),
    ]);
    $this->langcodes[] = $language->getId();
    $language->save();
  }

  /**
   * {@inheritdoc}
   */
  public function tearDown() {
    // Delete all data produced by testing module.
    foreach (['dummy', 'with_owner', 'multifield'] as $bundle) {
      foreach (['published', 'draft'] as $graph) {
        $query = <<<EndOfQuery
DELETE {
  GRAPH <http://example.com/$bundle/$graph> {
    ?entity ?field ?value
  }
}
WHERE {
  GRAPH <http://example.com/$bundle/$graph> {
    ?entity ?field ?value
  }
}
EndOfQuery;
        $this->sparql->query($query);
      }
    }

    parent::tearDown();
  }

}
