<?php

namespace MABI\RESTAccess\Testing;

include_once __DIR__ . '/../../../vendor/autoload.php';
include_once __DIR__ . '/../../../tests/middleware/MiddlewareTestCase.php';
include_once __DIR__ . '/../PostAndObjectOnly.php';

use \MABI\RESTAccess\PostAndObjectOnly;
use \MABI\Testing\MiddlewareTestCase;

class PostAndObjectOnlyTest extends MiddlewareTestCase {

  public function testStoppedCall() {
    $middleware = new PostAndObjectOnly();
    $this->setUpApp(array('PATH_INFO' => '/modelbs'), array($middleware));

    $this->app->call();

    $this->assertEquals(401, $this->app->getResponse()->status());
  }

  public function testPassedCall() {
    $middleware = new PostAndObjectOnly();
    $this->setUpApp(array('PATH_INFO' => '/modelbs/1'), array($middleware));

    $this->dataConnectionMock->expects($this->once())
      ->method('findOneByField')
      ->with('id', 1, 'modelbs')
      ->will($this->returnValue(array(
        'modelBId' => 1,
        'name' => 'test'
      )));

    $this->app->call();

    $this->assertEquals(200, $this->app->getResponse()->status());
  }

  public function testSkipDocs() {
    $middleware = new PostAndObjectOnly();
    $this->setUpApp(array('PATH_INFO' => '/justa/testfunc'), array($middleware));

    $docArray = array(
      'HTTPMethod' => 'test',
      'URI' => "/test",
      'Synopsis' => '',
      'parameters' => array()
    );
    $rClassMock = $this->getMock('\ReflectionClass', array(), array(), '', FALSE);
    $reflectionMethod = new \ReflectionMethod(get_class($this->restController), 'put');

    $middleware->documentMethod($rClassMock, $reflectionMethod, $docArray);
    $this->assertNull($docArray);
  }

  public function testFullDocs() {
    $middleware = new PostAndObjectOnly();
    $this->setUpApp(array('PATH_INFO' => '/justa/testfunc'), array($middleware));

    $docArray = array(
      'HTTPMethod' => 'test',
      'URI' => "/test",
      'Synopsis' => '',
      'parameters' => array()
    );
    $rClassMock = $this->getMock('\ReflectionClass', array(), array(), '', FALSE);
    $reflectionMethod = new \ReflectionMethod(get_class($this->restController), 'post');

    $middleware->documentMethod($rClassMock, $reflectionMethod, $docArray);
    $this->assertNotEmpty($docArray);
  }
}