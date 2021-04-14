<?php

declare(strict_types = 1);

namespace Drupal\Tests\rdf_taxonomy\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\sparql_entity_storage\Traits\SparqlConnectionTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\sparql_entity_storage\Entity\SparqlMapping;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests adding, editing and deleting terms in an unlocked vocabulary.
 *
 * @group rdf_taxonomy
 */
class ManageUnlockedVocabularyTermsTest extends BrowserTestBase {

  use SparqlConnectionTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rdf_draft',
    'rdf_taxonomy',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->setUpSparql();
    parent::setUp();

    FilterFormat::create(['format' => 'full_html'])->save();

    // Create an unlocked vocabulary and its mapping.
    Vocabulary::create([
      'vid' => 'unlocked_vocab',
      'name' => $this->randomString(),
    ])->setThirdPartySetting('rdf_taxonomy', 'locked', FALSE)
      ->save();
    SparqlMapping::create([
      'entity_type_id' => 'taxonomy_term',
      'bundle' => 'unlocked_vocab',
    ])->setRdfType('http://example.com/unlocked-vocab')
      ->setGraphs(['default' => 'http://example.com/graph/unlocked-vocab'])
      ->setEntityIdPlugin('default')
      ->setMappings([
        'vid' => [
          'target_id' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#inScheme',
            'format' => 'resource',
          ],
        ],
        'name' => [
          'value' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#prefLabel',
            'format' => 't_literal',
          ],
        ],
        'parent' => [
          'target_id' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#broaderTransitive',
            'format' => 'resource',
          ],
        ],
        'description' => [
          'value' => [
            'predicate' => 'http://www.w3.org/2004/02/skos/core#definition',
            'format' => 't_literal',
          ],
        ],
      ])->save();

    $this->drupalLogin($this->drupalCreateUser([
      'create terms in unlocked_vocab',
      'edit terms in unlocked_vocab',
      'delete terms in unlocked_vocab',
      'access taxonomy overview',
      'use text format full_html',
    ]));
  }

  /**
   * Tests adding, editing and deleting terms from an unlocked vocabulary.
   */
  public function testUnlocked() {
    // Tests creation of a new term via UI.
    $this->drupalGet('admin/structure/taxonomy/manage/unlocked_vocab/add');

    $assert_session = $this->assertSession();
    $assert_session->fieldNotExists('Weight');
    $edit = [
      'name[0][value]' => 'Top Level Term',
      'description[0][value]' => $this->randomString(),
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert_session->pageTextContains('Created new term Top Level Term.');

    /** @var \Drupal\taxonomy\TermStorageInterface $storage */
    $storage = $this->container->get('entity_type.manager')->getStorage('taxonomy_term');
    $terms = $storage->loadByProperties([
      'vid' => 'unlocked_vocab',
      'name' => 'Top Level Term',
    ]);
    /** @var \Drupal\taxonomy\TermInterface $term */
    $term = reset($terms);

    // Test term view.
    $this->drupalGet($term->toUrl());
    $assert_session->statusCodeEquals(200);

    // Tests term editing.
    $this->drupalGet($term->toUrl('edit-form'));
    $assert_session->fieldNotExists('Weight');
    $edit = [
      'name[0][value]' => 'Changed Term',
    ];
    $this->drupalPostForm(NULL, $edit, 'Save');
    $assert_session->pageTextContains('Updated term Changed Term.');

    // Test term weight.
    SparqlMapping::loadByName('taxonomy_term', 'unlocked_vocab')
      ->addMappings([
        'weight' => [
          'value' => [
            'predicate' => 'http://example.com/term/weight',
            'format' => 'xsd:integer',
          ],
        ],
      ])
      ->save();
    $this->drupalGet('admin/structure/taxonomy/manage/unlocked_vocab/add');
    $assert_session->fieldExists('Weight');

    $this->drupalGet($term->toUrl('edit-form'));
    $assert_session->fieldExists('Weight');
    $page = $this->getSession()->getPage();
    $page->fillField('Weight', 11);
    $page->pressButton('Save');
    $assert_session->fieldValueEquals('Weight', 11);

    // Tests term deletion.
    $page->clickLink('Delete');
    $assert_session->pageTextContains('Are you sure you want to delete the taxonomy term Changed Term?');
    $page->pressButton('Delete');
    $assert_session->pageTextContains('Deleted term Changed Term.');
  }

}
