<?php

declare(strict_types = 1);

namespace Drupal\joinup_group\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\joinup_group\Event\GroupMenuLinksEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to group events.
 */
class GroupMenuLinksSubscriber implements EventSubscriberInterface {

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

    $link = ['uri' => $group->toUrl()->toUriString()];
    $event->addMenuLink($link, $this->t('Overview'), -10);

    $link = ['uri' => $group->toUrl('member-overview')->toUriString()];
    $event->addMenuLink($link, $this->t('Members'), -9);

    $link = ['uri' => $group->toUrl('about-page')->toUriString()];
    $event->addMenuLink($link, $this->t('About'), -8);
  }

}
