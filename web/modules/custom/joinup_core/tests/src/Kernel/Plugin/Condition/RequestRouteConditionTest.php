<?php

declare(strict_types = 1);

namespace Drupal\Tests\joinup_core\Kernel\Plugin\Condition;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Tests that the Request Route condition plugin works as expected.
 *
 * @group Plugin
 *
 * @todo Replace this with the Route Condition module.
 *
 * @see https://citnet.tech.ec.europa.eu/CITnet/jira/browse/ISAICP-6062
 * @see https://www.drupal.org/project/route_condition
 */
class RequestRouteConditionTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'joinup_core',
    'rdf_entity',
    'sparql_entity_storage',
    'system',
  ];

  /**
   * The condition plugin manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $pluginManager;

  /**
   * The request stack used for testing.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', ['sequences']);

    $this->pluginManager = $this->container->get('plugin.manager.condition');
    $this->requestStack = new RequestStack();

    // At this point the current_route_match service has been already
    // initialised, so we need to override that too in order to make it use our
    // test request stack service.
    $current_route_match = new CurrentRouteMatch($this->requestStack);
    $this->container->set('current_route_match', $current_route_match);
  }

  /**
   * Tests the request route condition.
   */
  public function testConditions() {
    /** @var \Drupal\system\Plugin\Condition\RequestPath $condition */
    $condition = $this->pluginManager->createInstance('request_route');
    $condition->setConfig('routes', ['foo.test', 'bar.test.foo']);

    $this->addRequest('foo.test', '/foo/test');
    $this->assertTrue($condition->execute());

    $this->addRequest('bar.test', '/bar');
    $this->assertFalse($condition->execute());

    // Verify the correctness of the summary.
    $condition->setConfig('routes', ['foo.test', 'bar.test.foo']);
    $this->assertEquals('Return true on the following routes: foo.test, bar.test.foo', $condition->summary());

    // Test the summary when the plugin is negated.
    $condition->setConfig('negate', TRUE);
    $this->assertEquals('Do not return true on the following routes: foo.test, bar.test.foo', $condition->summary());
  }

  /**
   * Adds a request to the request stack.
   *
   * @param string $route_name
   *   The request route name.
   * @param string $path
   *   The request route path.
   */
  protected function addRequest($route_name, $path) {
    $request = Request::create($path);
    $request->attributes->set(RouteObjectInterface::ROUTE_NAME, $route_name);
    $request->attributes->set(RouteObjectInterface::ROUTE_OBJECT, new Route($path));
    $this->requestStack->push($request);
  }

}
