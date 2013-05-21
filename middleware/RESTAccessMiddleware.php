<?php

namespace MABI\Middleware;

include_once __DIR__ . '/../Middleware.php';
include_once __DIR__ . '/../Utilities.php';

abstract class RESTAccessMiddleware extends \MABI\Middleware {

  protected abstract function doesHaveAccessToMethod($methodName);

  /**
   * Blocks access to REST functions that this class specifies using the doesHaveAccessToMethod.
   *
   * @throws \Slim\Exception\Stop
   */
  public function call() {
    $callable = $this->getController()->getApp()->getSlim()->router()->getCurrentRoute()->getCallable();
    if (empty($callable) || !$this->doesHaveAccessToMethod($callable[1])) {
      $this->getController()->getApp()->getSlim()->response()->status(401);
      throw new \Slim\Exception\Stop();
    }

    if (!empty($this->next)) {
      $this->next->call();
    }
  }

  /**
   * Removes the documentation for methods that the api does not have access to
   *
   * @param \ReflectionClass $rClass
   * @param \ReflectionMethod $rMethod
   * @param array $methodDoc
   */
  public function documentMethod(\ReflectionClass $rClass, \ReflectionMethod $rMethod, array &$methodDoc) {
    parent::documentMethod($rClass, $rMethod, $methodDoc);

    if (!$this->doesHaveAccessToMethod($rMethod->name)) {
      $methodDoc = NULL;
      return;
    }

    if (!empty($this->next)) {
      $this->next->documentMethod($rClass, $rMethod, $methodDoc);
    }
  }

}
