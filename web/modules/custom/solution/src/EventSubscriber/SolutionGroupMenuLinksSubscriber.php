<?php

declare(strict_types = 1);

namespace Drupal\solution\EventSubscriber;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\joinup_group\Event\GroupMenuLinksEvent;
use Drupal\solution\Entity\SolutionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to group events.
 */
class SolutionGroupMenuLinksSubscriber implements EventSubscriberInterface {

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
    $solution = $event->getGroup();
    if ($solution instanceof SolutionInterface) {
      $link = [
        'uri' => Url::fromRoute('collection.glossary_page', [
          'rdf_entity' => $solution->getCollection()->id(),
          'letter' => NULL,
        ])->toUriString(),
        'options' => [
          'attributes' => [
            'class' => [
              'group-menu-link-external',
            ],
          ],
        ],
      ];
      $event->addMenuLink($link, $this->t('Glossary'), -7, [
        // Glossary menu link in solution is optional.
        'enabled' => FALSE,
      ]);
    }
  }

}
