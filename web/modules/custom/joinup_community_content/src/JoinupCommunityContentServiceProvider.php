<?php

namespace Drupal\joinup_community_content;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Replaces core's node revision access check with one that handles og roles.
 */
class JoinupCommunityContentServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $definition = $container->getDefinition('access_check.node.revision');
    $definition->setClass('Drupal\joinup_community_content\Access\NodeRevisionAccessCheck');
    $definition->addArgument(new Reference('og.group_type_manager'));
    $definition->addArgument(new Reference('og.access'));
    $definition->addArgument(new Reference('config.factory'));
  }

}
