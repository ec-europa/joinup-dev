<?php

declare(strict_types = 1);

namespace Drupal\oe_newsroom_newsletter;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\oe_newsroom_newsletter\NewsletterSubscriber\NewsletterSubscriber;
use Drupal\oe_newsroom_newsletter\NewsletterSubscriber\NewsletterSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Service for creating newsletter subscribers.
 *
 * This factory will return the standard subscriber by default but allows to
 * override the subscriber in settings.php:
 *
 * @code
 * $config['oe_newsroom_newsletter.subscriber']['class'] = 'Drupal\my_module\MySubscriber';
 * @endcode
 */
class SubscriberFactory implements SubscriberFactoryInterface {

  /**
   * The service container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new SubscriberFactory object.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The current service container.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   The configuration factory.
   */
  public function __construct(ContainerInterface $container, ConfigFactoryInterface $configFactory) {
    $this->container = $container;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public function get(): NewsletterSubscriberInterface {
    $subscriber_class = $this->configFactory->get('oe_newsroom_newsletter.subscriber')->get('class') ?? NewsletterSubscriber::class;
    return $subscriber_class::create($this->container);
  }

}
