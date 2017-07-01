<?php

namespace Drupal\joinup_migrate\EventSubscriber;

use Drupal\Core\Language\LanguageInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigratePreEntitySaveEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * A subscriber that acts before an entity is saved.
 */
class TranslateFieldSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [MigrateEvents::PRE_ENTITY_SAVE => 'translateField'];
  }

  /**
   * Reacts before an entity is saved.
   *
   * @param \Drupal\migrate\Event\MigratePreEntitySaveEvent $event
   *   The event object.
   */
  public function translateField(MigratePreEntitySaveEvent $event) {
    $row = $event->getRow();
    if (!$row->hasDestinationProperty('i18n') || !($i18n = $row->getDestinationProperty('i18n'))) {
      return;
    }

    // Create first all needed languages. We do this before the iteration where
    // we save the translations, otherwise the entity language static cache is
    // initialized with the initial languages list and that cannot be changed.
    $language_manager = \Drupal::languageManager();
    foreach (array_keys($i18n) as $langcode) {
      if (!$language_manager->getLanguage($langcode)) {
        ConfigurableLanguage::createFromLangcode($langcode)->save();
      }
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $event->getEntity();
    if (!$entity->isTranslatable()) {
      return;
    }

    foreach ($i18n as $langcode => $values) {
      // Fill with the specific content translation fields and fall-back to
      // the remaining values from the base translation.
      $values += [
        'content_translation_source' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
        'content_translation_status' => TRUE,
        'content_translation_uid' => 1,
        'content_translation_outdated' => FALSE,
      ] + $entity->toArray();
      // Create the translation.
      $entity->addTranslation($langcode, $values);
    }
  }

}
