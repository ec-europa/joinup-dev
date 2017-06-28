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
   * Reacts  before an entity is saved.
   *
   * @param \Drupal\migrate\Event\MigratePreEntitySaveEvent $event
   *   The event object.
   */
  public function translateField(MigratePreEntitySaveEvent $event) {
    $row = $event->getRow();
    if ($row->hasDestinationProperty('i18n') && ($i18n = $row->getDestinationProperty('i18n'))) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $event->getEntity();

      /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
      $language_manager = \Drupal::service('language_manager');
      /** @var \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager */
      $content_translation_manager = \Drupal::service('content_translation.manager');

      foreach (array_keys($i18n) as $langcode) {
        if (!in_array($langcode, array_keys($language_manager->getLanguages()))) {
          ConfigurableLanguage::createFromLangcode($langcode)->save();
        }
      }

      if (!$entity->isTranslatable()) {
        return;
      }

      foreach ($i18n as $langcode => $values) {
        $values += $entity->toArray();
        $values['content_translation_source'] = LanguageInterface::LANGCODE_NOT_SPECIFIED;
        $values['content_translation_status'] = TRUE;
        $values['content_translation_uid'] = 1;
        $values['content_translation_outdated'] = FALSE;
        $entity->addTranslation($langcode, $values);
      }
    }
  }

}
