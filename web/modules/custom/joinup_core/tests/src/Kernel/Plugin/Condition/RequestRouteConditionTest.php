<?php

namespace Drupal\Tests\joinup_core\Kernel;

use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\KernelTests\KernelTestBase;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Route;

/**
 * Tests that the Request Route condition plugin.
 *
 * @group Plugin
 */
class RequestRouteConditionTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'joinup_core', 'og'];

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
    /* @var \Drupal\system\Plugin\Condition\RequestPath $condition */
    $condition = $this->pluginManager->createInstance('request_route');
    $condition->setConfig('routes', "foo.test\r\nbar.test.foo");

    $this->addRequest('foo.test', '/foo/test');
    $this->assertTrue($condition->execute(), 'The request route condition matches for configured routes.');

    $this->addRequest('bar.test', '/bar');
    $this->assertFalse($condition->execute(), 'The request route condition does not match routes not configured.');

    // Verify the correctness of the summary.
    $condition->setConfig('routes', "foo.test\r\nbar.test.foo");
    $this->assertEquals($condition->summary(), 'Return true on the following routes: foo.test, bar.test.foo', 'The condition summary matches for configured routes.');

    // Test the summary when the plugin is negated.
    $condition->setConfig('negate', TRUE);
    $this->assertEquals($condition->summary(), 'Do not return true on the following routes: foo.test, bar.test.foo', 'The condition summary matches for configured routes.');
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
