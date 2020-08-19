<?php

declare(strict_types = 1);

namespace Drupal\collection\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\collection\Entity\CollectionInterface;
use Drupal\joinup_group\Event\GroupMenuLinksEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to group events.
 */
class CollectionGroupMenuLinksSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      GroupMenuLinksEvent::ADD_LINKS => 'addLinks',
    ];
  }

  /**
   * Listens to GroupMenuLinksEvent::ADD_LINKS event.
   *
   * @param \Drupal\joinup_group\Event\GroupMenuLinksEvent $event
   *   The event object.
   */
  public function addLinks(GroupMenuLinksEvent $event): void {
    $group = $event->getGroup();
    if ($group instanceof CollectionInterface) {
      $link = [
        'uri' => Url::fromRoute('collection.glossary_page', [
          'rdf_entity' => $group->id(),
          'letter' => NULL,
        ])->toUriString(),
      ];
      $event->addMenuLink($link, $this->t('Glossary'), -7);
    }
  }

}
