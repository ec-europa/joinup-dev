<?php

declare(strict_types = 1);

namespace Drupal\solution\Plugin\Action;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Entity\OgMembership;
use Drupal\og\Og;
use Drupal\rdf_entity\RdfInterface;
use Drupal\user\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an action allowing an admin to transfer a solution ownership.
 *
 * @Action(
 *   id = "joinup_transfer_solution_ownership",
 *   label = @Translation("Transfer solution ownership"),
 *   type = "og_membership",
 *   confirm_form_route_name = "solution.transfer_ownership_confirm",
 * )
 */
class TransferSolutionOwnershipAction extends ActionBase implements ContainerFactoryPluginInterface {

  use StringTranslationTrait;

  /**
   * The tempstore service.
   *
   * @var \Drupal\user\PrivateTempStore
   */
  protected $tempStore;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Messages collector in case of warnings or errors.
   *
   * @var array[]
   */
  protected $messages = [];

  /**
   * Constructs a new action object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\user\PrivateTempStoreFactory $temp_store_factory
   *   The tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, PrivateTempStoreFactory $temp_store_factory, AccountInterface $current_user, RouteMatchInterface $route_match) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->tempStore = $temp_store_factory->get('joinup_transfer_solution_ownership');
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('user.private_tempstore'),
      $container->get('current_user'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $memberships) {
    // We only allow one user to pickup the membership.
    if (count($memberships) > 1) {
      $this->messages['warning'][] = $this->t('You cannot transfer the solution ownership to more than one user. Please select a single user.');
    }
    parent::executeMultiple($memberships);
  }

  /**
   * {@inheritdoc}
   */
  public function execute(OgMembership $membership = NULL) {
    // Is the selected member already the owner?
    if ($membership->hasRole('rdf_entity-solution-administrator')) {
      $this->messages['warning'][] = $this->t('Member %member is already the owner of %solution solution. Please select other user.', [
        '%member' => $membership->getOwner()->label(),
        '%solution' => $membership->getGroup()->label(),
      ]);
    }

    // After validating, store the data in the user private tempstore. We'll
    // retrieve the information later, in the confirm form submit callback or in
    // the redirect alter event subscriber.
    // @see \Drupal\solution\Form\TransferSolutionOwnershipConfirmForm
    // @see \Drupal\solution\EventSubscriber\TransferSolutionOwnershipSubscriber
    $this->tempStore->set($this->currentUser->id(), [
      'membership' => $membership->id(),
      'messages' => $this->messages,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function access($membership, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\og\OgMembershipInterface $membership */
    /** @var \Drupal\rdf_entity\RdfInterface $solution */
    $solution = $membership->getGroup();
    $can_transfer_ownership = $this->canTransferOwnership($solution, $account);
    return $return_as_object ? ($can_transfer_ownership ? AccessResult::allowed() : AccessResult::forbidden()) : $can_transfer_ownership;
  }

  /**
   * Checks if a user is able to transfer the ownership of a given solution.
   *
   * The ownership transfer is allowed only for solutions and when the transfer
   * is triggered either by a user that has site-wide permissions for such
   * operations ('administer solution ownership') or the user is the current
   * owner of the solution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution whom ownership is about to be transferred.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to be checked.
   *
   * @return bool
   *   If the user is allowed to transfer the ownership of the solution.
   */
  public function canTransferOwnership(RdfInterface $solution, AccountInterface $account) : bool {
    return
      // The RDF entity is a solution and...
      $solution->bundle() === 'solution' &&
      (
        // The user has proper site-wide permission for this operation.
        $account->hasPermission('administer solution ownership') ||
        // Or the user is the current solution owner.
        $this->isSolutionOwner($solution, $account)
      );
  }

  /**
   * Finds out if the given account is owner of the given solution.
   *
   * @param \Drupal\rdf_entity\RdfInterface $solution
   *   The solution.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The use account.
   *
   * @return bool
   *   If the given account is owner of the given solution.
   */
  protected function isSolutionOwner(RdfInterface $solution, AccountInterface $account) : bool {
    return
      ($membership = Og::getMembership($solution, $account)) &&
      $membership->hasRole('rdf_entity-solution-administrator');
  }

}
