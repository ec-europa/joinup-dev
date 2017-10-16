<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\custom_page\CustomPageOgMenuLinksUpdater' "modules/custom/custom_page/src".
 */

namespace Drupal\custom_page\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\custom_page\CustomPageOgMenuLinksUpdater.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class CustomPageOgMenuLinksUpdater implements \Drupal\custom_page\CustomPageOgMenuLinksUpdaterInterface
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
         * @var \Drupal\custom_page\CustomPageOgMenuLinksUpdater
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
        public function addLink(\Drupal\node\NodeInterface $custom_page)
        {
            return $this->lazyLoadItself()->addLink($custom_page);
        }

        /**
         * {@inheritdoc}
         */
        public function deleteLinks(\Drupal\node\NodeInterface $custom_page)
        {
            return $this->lazyLoadItself()->deleteLinks($custom_page);
        }

    }

}
