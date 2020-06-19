<?php

declare(strict_types = 1);

namespace Drupal\joinup_search\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Solarium\Core\Client\Client;
use Solarium\Core\Event\Events;
use Solarium\Core\Event\PreExecute;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber to handle Solarium events.
 */
class SolariumEventSubscriber implements EventSubscriberInterface {

  /**
   * The state key value store.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a SolariumEventSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state key value store.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

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

    if ($this->state->get('joinup_search.skip_solr_commit_on_update', FALSE) === TRUE) {
      return;
    }

    if ($query->getType() !== Client::QUERY_UPDATE) {
      return;
    }

    // Soft commit and wait for a new searcher to be opened.
    /** @var \Solarium\QueryType\Update\Query\Query $query */
    $query->addCommit(TRUE, TRUE);
  }

}
