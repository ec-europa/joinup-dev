<?php

declare(strict_types = 1);

namespace Drupal\Tests\sparql_entity_storage\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\sparql_test\Entity\SparqlTest;

/**
 * Tests SPARQL entity storage translation functionality.
 *
 * @group sparql_entity_storage
 */
class EntityLanguageTest extends SparqlKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['language'];

  /**
   * Test entity translation.
   */
  public function testEntityTranslation(): void {
    foreach ($langcodes = ['el', 'de'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    /** @var \Drupal\sparql_test\Entity\SparqlTest $entity */
    $entity = SparqlTest::create([
      'id' => 'http://example.com/apple',
      'type' => 'fruit',
      'title' => 'Apple',
      'text' => 'some text',
    ]);

    foreach ($langcodes as $langcode) {
      $entity->addTranslation($langcode, [
        'title' => "$langcode translation",
      ] + $entity->toArray());
    }
    $entity->save();

    $entity = SparqlTest::load('http://example.com/apple');
    foreach ($langcodes as $langcode) {
      $translation = $entity->getTranslation($langcode);
      $this->assertEquals("$langcode translation", $translation->label());
      // The field_text field is non-translatable.
      $this->assertEquals('some text', $translation->text->value);
    }

    // For a single translation, update the text field.
    $translation = $entity->getTranslation('el');
    $translation->set('text', 'Τυχαιο κειμενο');
    $translation->save();

    $entity = SparqlTest::load('http://example.com/apple');

    // Make sure we are using the default language.
    $untranslated = $entity->getUntranslated();
    $this->assertNotEquals('el', $untranslated->language()->getId());

    // Test that the untranslatable field affected all translations.
    $this->assertEquals('Τυχαιο κειμενο', $untranslated->text->value);

    $entity->removeTranslation('el');
    $entity->save();

    $entity = SparqlTest::load('http://example.com/apple');

    // The value for the non-translatable field should persist.
    $this->assertEquals('Τυχαιο κειμενο', $entity->text->value);

    // Verify that the translation is deleted.
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid translation language (el) specified.');
    $entity->getTranslation('el');
  }

}
