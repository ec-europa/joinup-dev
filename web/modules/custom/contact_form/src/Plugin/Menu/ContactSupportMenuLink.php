<?php

declare(strict_types = 1);

namespace Drupal\contact_form\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;
use Drupal\Core\Menu\StaticMenuLinkOverridesInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcherInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a custom menu link that leads to the Contact Joinup Support form.
 */
class ContactSupportMenuLink extends MenuLinkDefault {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The path matcher.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  protected $pathMatcher;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The current path stack.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Creates a new menu link instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Menu\StaticMenuLinkOverridesInterface $static_override
   *   The static override storage.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Path\PathMatcherInterface $path_matcher
   *   The path matcher.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path_stack
   *   The current path stack.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, StaticMenuLinkOverridesInterface $static_override, AccountInterface $current_user, PathMatcherInterface $path_matcher, RequestStack $request_stack, CurrentPathStack $current_path_stack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $static_override);

    $this->currentUser = $current_user;
    $this->pathMatcher = $path_matcher;
    $this->requestStack = $request_stack;
    $this->currentPathStack = $current_path_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('menu_link.static.overrides'),
      $container->get('current_user'),
      $container->get('path.matcher'),
      $container->get('request_stack'),
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function isDeletable(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return [
      'url',
      'url.query_args',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOptions() {
    $request = $this->requestStack->getCurrentRequest();

    // If a 'destination' query argument exists, then this is what the user
    // should return to.
    if ($request->query->has('destination')) {
      // The path should start with a slash.
      $destination = '/' . ltrim($request->query->get('destination'), '/');
      $return_to = Url::fromUserInput($destination)->setAbsolute()->toString();
    }
    // If we are on the homepage, set the 'destination' path to '/' alias.
    elseif ($this->pathMatcher->isFrontPage()) {
      $return_to = '/';
    }
    else {
      $return_to = Url::fromUri('internal:' . $this->currentPathStack->getPath($request))->toString();
    }

    $options = parent::getOptions();
    $options['query']['destination'] = $return_to;

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteLink() {
  }

}
