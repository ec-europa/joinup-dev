<?php

namespace Drupal\Tests\rdf_entity\Kernel;

use Drupal\rdf_entity\Entity\Rdf;

/**
 * Tests rdf_entity translation functionality.
 *
 * @group rdf_entity
 */
class RdfEntityLanguageTest extends RdfEntityLanguageTestBase {

  /**
   * Test entity translation.
   */
  public function testEntityTranslation() {
    $id = 'http://translation.rdfentity/1';
    // Create a dummy test.
    $entity = Rdf::create([
      'id' => $id,
      'rid' => 'dummy',
      'label' => 'Foo',
      'field_text' => 'some text',
    ]);
    $entity->save();
    $label_array = [];
    foreach ($this->langcodes as $langcode) {
      $label_array[$langcode] = 'Foo' . $langcode;
      $entity->addTranslation($langcode, [
        'id' => $id,
        'rid' => 'dummy',
        'label' => $label_array[$langcode],
        'field_text' => 'some text',
      ]);
    }
    $entity->save();

    /** @var \Drupal\rdf_entity\RdfInterface $loaded */
    $loaded = $this->entityManager->getStorage('rdf_entity')->loadUnchanged($id);
    foreach ($this->langcodes as $langcode) {
      $translation = $loaded->getTranslation($langcode);
      $this->assertEquals($label_array[$langcode], $translation->label());
      // The field_text field is non translatable and will.
      $this->assertEquals('some text', $translation->get('field_text')->first()->value);
    }

    // For a single translation, update the field_text field.
    $translation = $loaded->getTranslation('el');
    $translation->set('field_text', 'Τυχαιο κειμενο');
    $translation->save();

    /** @var \Drupal\rdf_entity\RdfInterface $loaded */
    $loaded = $this->entityManager->getStorage('rdf_entity')->loadUnchanged($id);
    // Make sure we are using the default language.
    $untranslated = $loaded->getUntranslated();
    $this->assertNotEquals('el', $untranslated->language()->getId());

    // Test that the untranslatable field affected all translations.
    $this->assertEquals('Τυχαιο κειμενο', $untranslated->get('field_text')->value);

    $loaded->removeTranslation('el');
    $loaded->save();

    /** @var \Drupal\rdf_entity\RdfInterface $loaded */
    $loaded = $this->entityManager->getStorage('rdf_entity')->loadUnchanged($id);
    // The value for the non traslatable field should persist.
    $this->assertEquals('Τυχαιο κειμενο', $loaded->get('field_text')->value);

    // Verify that the translation is deleted.
    $this->setExpectedException('\InvalidArgumentException', "Invalid translation language (el) specified.");
    $loaded->getTranslation('el');
  }

}
