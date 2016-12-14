<?php

namespace Drupal\joinup_search\EventSubscriber;

use Solarium\Core\Event\Events;
use Solarium\Core\Event\PreExecute;
use Solarium\QueryType\Update\Query\Query;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle Solarium events.
 */
class SolariumEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      Events::PRE_EXECUTE => 'commitOnUpdate',
    ];
  }

  /**
   * Makes all update queries soft commit on the index.
   *
   * @param \Solarium\Core\Event\PreExecute $event
   *   The pre execute event.
   */
  public function commitOnUpdate(PreExecute $event) {
    $query = $event->getQuery();

    if (!$query instanceof Query) {
      return;
    }

    // Soft commit and wait for a new searcher to be opened.
    $query->addCommit(TRUE, TRUE);
  }

}
