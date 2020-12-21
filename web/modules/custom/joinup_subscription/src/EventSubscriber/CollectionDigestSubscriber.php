<?php

declare(strict_types = 1);

namespace Drupal\joinup_subscription\EventSubscriber;

use Drupal\collection\Entity\CollectionContentInterface;
use Drupal\joinup_group\Entity\GroupContentInterface;
use Drupal\joinup_notification\Event\NotificationEvent;
use Drupal\joinup_notification\NotificationEvents;
use Drupal\joinup_subscription\DigestFormatter;
use Drupal\solution\Entity\SolutionInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Subscriber for compiling content subscription digest messages.
 */
class CollectionDigestSubscriber extends GroupContentDigestSubscriberBase implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      NotificationEvents::COMMUNITY_CONTENT_CREATE => 'notifyOnCommunityContentCreation',
      NotificationEvents::COMMUNITY_CONTENT_UPDATE => 'notifyOnCommunityContentPublication',
      NotificationEvents::RDF_ENTITY_CRUD => 'notifyOnRdfEntityCrudOperation',
    ];
  }

  /**
   * Notifies subscribed users when a new solution is added to the collection.
   *
   * @param \Drupal\joinup_notification\Event\NotificationEvent $event
   *   The event object.
   */
  public function notifyOnRdfEntityCrudOperation(NotificationEvent $event) {
    // Only act on entities that are being created or updated. Subscribers are
    // not notified about solutions that are being removed.
    if (!in_array($event->getOperation(), ['create', 'update'])) {
      return;
    }

    // We are only concerned about solutions that belong to a collection, are
    // published and are newly created or are being published for the first
    // time. Let's filter it down.
    /** @var \Drupal\rdf_entity\RdfInterface $entity */
    $entity = $event->getEntity();
    if (
      !$entity instanceof SolutionInterface ||
      $entity->get('collection')->isEmpty() ||
      !$entity->isPublished() ||
      // Note: the `->hasPublished` property is a hack that will be removed once
      // we have revisionable RDF entities.
      // @see joinup_group_entity_presave()
      (!$entity->isNew() && $entity->hasPublished)
    ) {
      return;
    }

    $this->sendMessage($entity, $this->getTemplateId());
  }

  /**
   * {@inheritdoc}
   */
  protected function getGroupId(GroupContentInterface $entity): string {
    assert($entity instanceof CollectionContentInterface);
    return $entity->getCollection()->id();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateFieldName(): string {
    return 'field_collection_content';
  }

  /**
   * {@inheritdoc}
   */
  protected function getTemplateId(): string {
    return DigestFormatter::DIGEST_TEMPLATE_IDS['collection'];
  }

}
