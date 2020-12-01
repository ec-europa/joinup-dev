<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\joinup_subscription\DigestFormatter;
use Drupal\solution\Entity\SolutionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for compiling solution content subscription digest messages .
 */
class SolutionContentSubscriptionSubscriber extends GroupContentSubscriptionSubscriberBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      NotificationEvents::COMMUNITY_CONTENT_CREATE => 'notifyOnCommunityContentCreation',
      NotificationEvents::COMMUNITY_CONTENT_UPDATE => 'notifyOnCommunityContentPublication',
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupId(ContentEntityInterface $entity): string {
    $solution = $entity->getGroup();

    if (!($solution instanceof SolutionInterface)) {
      throw new \InvalidArgumentException('Parent group is not a solution.');
    }

    return $solution->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateFieldName(): string {
    return 'field_solution_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateId(): string {
    return DigestFormatter::DIGEST_TEMPLATE_IDS['solution'];
  }


}
