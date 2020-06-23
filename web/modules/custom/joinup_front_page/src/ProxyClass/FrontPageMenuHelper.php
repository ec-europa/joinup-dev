<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\joinup_front_page\FrontPageMenuHelper' "modules/custom/joinup_front_page/src".
 */

namespace Drupal\joinup_front_page\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\joinup_front_page\FrontPageMenuHelper.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class FrontPageMenuHelper implements \Drupal\joinup_front_page\FrontPageMenuHelperInterface
    {

        use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

        /**
         * The id of the original proxied service.
         *
         * @var string
         */
        protected $drupalProxyOriginalServiceId;

        /**
         * The real proxied service, after it was lazy loaded.
         *
         * @var \Drupal\joinup_front_page\FrontPageMenuHelper
         */
        protected $service;

        /**
         * The service container.
         *
         * @var \Symfony\Component\DependencyInjection\ContainerInterface
         */
        protected $container;

        /**
         * Constructs a ProxyClass Drupal proxy object.
         *
         * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
         *   The container.
         * @param string $drupal_proxy_original_service_id
         *   The service ID of the original service.
         */
        public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $drupal_proxy_original_service_id)
        {
            $this->container = $container;
            $this->drupalProxyOriginalServiceId = $drupal_proxy_original_service_id;
        }

        /**
         * Lazy loads the real service from the container.
         *
         * @return object
         *   Returns the constructed real service.
         */
        protected function lazyLoadItself()
        {
            if (!isset($this->service)) {
                $this->service = $this->container->get($this->drupalProxyOriginalServiceId);
            }

            return $this->service;
        }

        /**
         * {@inheritdoc}
         */
        public function getFrontPageMenuItem(\Drupal\Core\Entity\EntityInterface $entity) : ?\Drupal\menu_link_content\MenuLinkContentInterface
        {
            return $this->lazyLoadItself()->getFrontPageMenuItem($entity);
        }

        /**
         * {@inheritdoc}
         */
        public function pinToFrontPage(\Drupal\Core\Entity\FieldableEntityInterface $entity) : void
        {
            $this->lazyLoadItself()->pinToFrontPage($entity);
        }

        /**
         * {@inheritdoc}
         */
        public function unpinFromFrontPage(\Drupal\Core\Entity\FieldableEntityInterface $entity) : void
        {
            $this->lazyLoadItself()->unpinFromFrontPage($entity);
        }

    }

}
