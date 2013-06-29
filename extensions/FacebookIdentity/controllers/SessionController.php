<?php

namespace MABI\FacebookIdentity;

include_once __DIR__ . '/../../../RESTModelController.php';
include_once __DIR__ . '/../../Identity/controllers/SessionController.php';

use MABI\Identity\Identity;
use Slim\Exception\Stop;

/**
 * todo: docs
 *
 * @middleware \MABI\RESTAccess\PostAndObjectOnly
 * @middleware \MABI\Identity\Middleware\SessionHeader
 * @middleware \MABI\Identity\Middleware\RESTOwnerOnlyAccess
 */
class SessionController extends \MABI\Identity\SessionController {

  /**
   * @var \Mabi\Identity\Session
   */
  protected $model;

  protected $userModelClass = '\MABI\FacebookIdentity\User';

  protected function insertFBUser($fbData) {
    /**
     * @var $userModel \MABI\FacebookIdentity\User
     */
    $userModel = call_user_func($this->userModelClass . '::init', $this->getApp());

    // todo: get firstName and lastName from fb
    $userModel->firstName = 'test';
    $userModel->lastName = 'test';
    $userModel->email = $fbData->email;
    $userModel->password = uniqid();
    $userModel->facebookId = $fbData->id;

    Identity::insertUser($userModel);

    return $userModel;
  }

  /**
   * Creates a session. todo: docs
   *
   * @docs-param email string body optional todo: docs
   * @docs-param password string body optional todo: docs
   * @docs-param accessToken string body optional todo: docs
   *
   * @throws \Slim\Exception\Stop
   */
  function _restPostCollection() {
    $this->model = call_user_func($this->modelClass . '::init', $this->getApp());
    $this->model->loadParameters($this->getApp()->getSlim()->request()->post());

    if (empty($this->model->accessToken)) {
      if ($this->getExtension()->getFacebookOnly()) {
        $this->getApp()->getSlim()->response()->status(400);
        throw new Stop("An authorization token is required to create a session");
      }

      parent::_restPostCollection();
    }
    else {
      // get facebook info and login or create a user
      $fbData = $this->extension->getFBInfo($this->model->accessToken);
      // todo: handle exceptions

      /**
       * @var $userModel \MABI\FacebookIdentity\User
       */
      $userModel = call_user_func($this->userModelClass . '::init', $this->getApp());
      $this->model->newUserCreated = FALSE;

      if (!$userModel->findByField('facebookId', $fbData->id)) {
        $userModel = $this->insertFBUser($fbData);
        $this->model->newUserCreated = TRUE;
      }

      $this->model->created = new \DateTime('now');
      $this->model->lastAccessed = new \DateTime('now');
      $this->model->user = $userModel->getId();
      $this->model->insert();
      echo $this->model->outputJSON();
    }
  }

  protected function getDocParameters(\ReflectionMethod $rMethod) {
    $docParameters = parent::getDocParameters($rMethod);

    if ($rMethod->getName() == '_restPostCollection' && $this->getExtension()->getFacebookOnly()) {
      // remove email & password if facebook only is enabled
      foreach ($docParameters as $k => $docParameter) {
        switch ($docParameter['Name']) {
          case 'email':
          case 'password':
            unset($docParameters[$k]);
            break;
          case 'accessToken':
            $docParameters[$k]['Required'] = 'Y';
        }
      }
    }

    return $docParameters;
  }


}
