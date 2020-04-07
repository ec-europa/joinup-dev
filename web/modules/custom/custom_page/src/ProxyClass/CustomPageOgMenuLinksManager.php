<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\custom_page\CustomPageOgMenuLinksManager' "modules/custom/custom_page/src".
 */

namespace Drupal\custom_page\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\custom_page\CustomPageOgMenuLinksManager.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class CustomPageOgMenuLinksManager implements \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
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
         * @var \Drupal\custom_page\CustomPageOgMenuLinksManager
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
        public function getChildren(\Drupal\node\NodeInterface $custom_page) : array
        {
            return $this->lazyLoadItself()->getChildren($custom_page);
        }

        /**
         * {@inheritdoc}
         */
        public function addLink(\Drupal\node\NodeInterface $custom_page) : \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
        {
            return $this->lazyLoadItself()->addLink($custom_page);
        }

        /**
         * {@inheritdoc}
         */
        public function moveLinks(\Drupal\node\NodeInterface $custom_page, $group_id) : \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
        {
            return $this->lazyLoadItself()->moveLinks($custom_page, $group_id);
        }

        /**
         * {@inheritdoc}
         */
        public function deleteLinks(\Drupal\node\NodeInterface $custom_page) : \Drupal\custom_page\CustomPageOgMenuLinksManagerInterface
        {
            return $this->lazyLoadItself()->deleteLinks($custom_page);
        }

        /**
         * {@inheritdoc}
         */
        public function getOgMenuInstanceByCustomPage(\Drupal\node\NodeInterface $custom_page) : ?\Drupal\og_menu\OgMenuInstanceInterface
        {
            return $this->lazyLoadItself()->getOgMenuInstanceByCustomPage($custom_page);
        }

        /**
         * {@inheritdoc}
         */
        public function getOgMenuInstanceByGroupId(string $group_id) : ?\Drupal\og_menu\OgMenuInstanceInterface
        {
            return $this->lazyLoadItself()->getOgMenuInstanceByGroupId($group_id);
        }

    }

}
