<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\joinup_core\JoinupRelationManager' "modules/custom/joinup_core/src".
 */

namespace Drupal\joinup_core\ProxyClass {

    /**
     * Provides a proxy class for \Drupal\joinup_core\JoinupRelationManager.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class JoinupRelationManager implements \Drupal\joinup_core\JoinupRelationManagerInterface, \Drupal\Core\DependencyInjection\ContainerInjectionInterface
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
         * @var \Drupal\joinup_core\JoinupRelationManager
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
        public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container)
        {
            \Drupal\joinup_core\JoinupRelationManager::create($container);
        }

        /**
         * {@inheritdoc}
         */
        public function getParent(\Drupal\Core\Entity\EntityInterface $entity)
        {
            return $this->lazyLoadItself()->getParent($entity);
        }

        /**
         * {@inheritdoc}
         */
        public function getParentModeration(\Drupal\Core\Entity\EntityInterface $entity)
        {
            return $this->lazyLoadItself()->getParentModeration($entity);
        }

        /**
         * {@inheritdoc}
         */
        public function getParentState(\Drupal\Core\Entity\EntityInterface $entity)
        {
            return $this->lazyLoadItself()->getParentState($entity);
        }

        /**
         * {@inheritdoc}
         */
        public function getParentElibrary(\Drupal\Core\Entity\EntityInterface $entity)
        {
            return $this->lazyLoadItself()->getParentElibrary($entity);
        }

        /**
         * {@inheritdoc}
         */
        public function getGroupOwners(\Drupal\Core\Entity\EntityInterface $entity, array $state = array (
          0 => 'active',
        ))
        {
            return $this->lazyLoadItself()->getGroupOwners($entity, $state);
        }

        /**
         * {@inheritdoc}
         */
        public function getGroupUsers(\Drupal\Core\Entity\EntityInterface $entity, array $state = array (
          0 => 'active',
        ))
        {
            return $this->lazyLoadItself()->getGroupUsers($entity, $state);
        }

        /**
         * {@inheritdoc}
         */
        public function getGroupMemberships(\Drupal\Core\Entity\EntityInterface $entity, array $state = array (
          0 => 'active',
        ))
        {
            return $this->lazyLoadItself()->getGroupMemberships($entity, $state);
        }

        /**
         * {@inheritdoc}
         */
        public function getUserMembershipsByRole(\Drupal\Core\Session\AccountInterface $user, $role, array $state = array (
          0 => 'active',
        ))
        {
            return $this->lazyLoadItself()->getUserMembershipsByRole($user, $role, $state);
        }

        /**
         * {@inheritdoc}
         */
        public function getCollectionsWhereSoleOwner(\Drupal\Core\Session\AccountInterface $user)
        {
            return $this->lazyLoadItself()->getCollectionsWhereSoleOwner($user);
        }

    }

}
