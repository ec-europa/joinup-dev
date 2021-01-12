<?php

declare(strict_types = 1);

namespace Drupal\pipeline_log\EventSubscriber;

use Drupal\Component\Datetime\Time;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\pipeline\Event\PipelineCompleteEvent;
use Drupal\pipeline\PipelineEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Pipeline logging event subscriber.
 *
 * The sole purpose of this subscriber is to note down when was the last time
 * each pipeline was ran.
 */
class PipelineLogSubscriber implements EventSubscriberInterface {

  /**
   * The key value factory service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  protected $time;

  /**
   * Constructs event subscriber.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $keyValue
   *   The key value factory service.
   * @param \Drupal\Component\Datetime\Time $time
   *   The time service.
   */
  public function __construct(KeyValueFactoryInterface $keyValue, Time $time) {
    $this->keyValue = $keyValue;
    $this->time = $time;
  }

  /**
   * Acts on pipeline completion.
   *
   * @param \Drupal\pipeline\Event\PipelineCompleteEvent $event
   *   The pipeline event.
   */
  public function onPipelineComplete(PipelineCompleteEvent $event): void {
    if (!$event->isSuccess()) {
      // We do not care about failed attempts.
      return;
    }
    $pipeline_id = $event->getPipeline()->getPluginId();
    $collection = $this->keyValue->get('pipeline_log');
    $collection->set($pipeline_id, $this->time->getRequestTime());
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      PipelineEvents::PIPELINE_COMPLETE => ['onPipelineComplete'],
    ];
  }

}
