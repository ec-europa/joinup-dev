<?php
// @codingStandardsIgnoreFile

/**
 * This file was generated via php core/scripts/generate-proxy-class.php 'Drupal\joinup_group\JoinupGroupManager' "modules/custom/joinup_group/src".
 */

namespace Drupal\joinup_group\ProxyClass {

  use Drupal\og\OgMembershipInterface;

  /**
     * Provides a proxy class for \Drupal\joinup_group\JoinupGroupManager.
     *
     * @see \Drupal\Component\ProxyBuilder
     */
    class JoinupGroupManager implements \Drupal\joinup_group\JoinupGroupManagerInterface
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
         * @var \Drupal\joinup_group\JoinupGroupManager
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
        public function getGroupsWhereSoleOwner(\Drupal\Core\Session\AccountInterface $user) : array
        {
            return $this->lazyLoadItself()->getGroupsWhereSoleOwner($user);
        }

        /**
         * {@inheritdoc}
         */
        public function getUserGroupMembershipsByBundle(\Drupal\Core\Session\AccountInterface $user, string $entity_type_id, string $bundle_id, array $states = array (
          0 => 'active',
        )) : array
        {
            return $this->lazyLoadItself()->getUserGroupMembershipsByBundle($user, $entity_type_id, $bundle_id, $states);
        }

        /**
         * {@inheritdoc}
         */
        public function getUserMembershipsByRole(\Drupal\Core\Session\AccountInterface $user, string $role, array $states = array (
          OgMembershipInterface::STATE_ACTIVE,
        )) : array
        {
            return $this->lazyLoadItself()->getUserMembershipsByRole($user, $role, $states);
        }

        /**
         * {@inheritdoc}
         */
        public function getGroupOwners(\Drupal\Core\Entity\EntityInterface $entity, array $states = array (
          0 => 'active',
        )) : array
        {
            return $this->lazyLoadItself()->getGroupOwners($entity, $states);
        }

    }

}
