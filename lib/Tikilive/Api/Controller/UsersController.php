<?php

namespace Tikilive\Api\Controller;

use Tikilive\Application\ParameterContainer;
use Tikilive\Controller\AbstractController;
use Tikilive\Exception\Http\BadRequestException;
use Tikilive\Exception\Http\NotFoundException;
use Tikilive\Exception\Validation\ValidationException;
use Tikilive\Http\JsonResponse;
use Tikilive\Model\Entity\UserEntity;
use Tikilive\Model\Store\UserStore;

/**
 * The API interface to User collection.
 */
class UsersController extends AbstractController
{
  /**
   * REST API: GET method.
   *
   * Retrieves all users or a single user if a specific user ID is requested.
   *
   * @param ParameterContainer $params Route parameters.
   */
  public function getMethod(ParameterContainer $params)
  {
    $id = $params->get('id');
    if ($id) {
      return $this->getUser($id);
    } else {
      return $this->getUsers();
    }
  }

  /**
   * Returns all users.
   *
   * @return array An array of users.
   */
  protected function getUsers()
  {
    // Reqeust parameters.
    $request = $this->get('request');

    $offset    = (int) $request->get('offset', 0);
    $limit     = (int) $request->get('limit', 30);
    $orderBy   = $request->get('order_by', 'username');
    $direction = $request->get('direction', 'ASC');

    // Retrieve data from model.
    $userStore = $this->getModel('UserStore');
    try {
      $users = $userStore->findAllActive($offset, $limit, $orderBy, $direction);
    } catch(\InvalidArgumentException $e) {
      throw new BadRequestException($e->getMessage());
    }

    // Format the response.
    $response = array();
    foreach($users as $user) {
      $response[] = array(
        'id'       => $user->getId(),
        'username' => $user->getUsername()
      );
    }

    // Add pager information.
    $response = new JsonResponse($response);
    $response->setCustom('pager', array(
      'offset' => $offset,
      'limit'  => $limit,
      'total'  => $userStore->countAllActive()
    ));

    return $response;
  }

  /**
   * Returns a user.
   *
   * @param int $id The user ID.
   * @return array An associative array containing user info.
   */
  protected function getUser($id)
  {
    $userStore = $this->getModel('UserStore');

    $user = $userStore->findById($id);

    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    if ($user->getStatus() === 'disabled') {
      throw new NotFoundException('User is no longer available.');
    }

    $response = array(
      'id'       => $user->getId(),
      'username' => $user->getUsername()
    );

    return $response;
  }

  /**
   * REST API: POST method.
   *
   * Creates a new user.
   *
   * @param ParameterContainer $params Route parameters.
   */
  public function postMethod(ParameterContainer $params)
  {
    $request = $this->get('request');

    $user = $this->getModel('UserEntity');
    $user->setUsername($request->post('username'));
    $user->setEmail($request->post('email'));
    $user->setStatus($request->post('status', 'enabled'));

    $userStore = $this->getModel('UserStore');
    try {
      $userId = $userStore->insert($user);
    } catch(ValidationException $e) {
      $new = new BadRequestException($e->getMessage());
      $new->setErrors($e->getErrors());
      throw $new;
    }

    return array(
      'id' => $userId
    );
  }

  /**
   * REST API: PUT method.
   *
   * Updates an existing user.
   *
   * @param ParameterContainer $params Route parameters.
   */
  public function putMethod(ParameterContainer $params)
  {
    $userStore = $this->getModel('UserStore');
    $user = $userStore->findById($params->get('id'));

    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    $request = $this->get('request');

    $user->setUsername($request->post('username', $user->getUsername()));
    $user->setEmail($request->post('email', $user->getEmail()));
    $user->setStatus($request->post('status', $user->getStatus()));

    try {
      $userId = $userStore->update($user);
    } catch(ValidationException $e) {
      $new = new BadRequestException($e->getMessage());
      $new->setErrors($e->getErrors());
      throw $new;
    }
  }

  /**
   * REST API: DELETE method.
   *
   * Deletes an existing user.
   *
   * @param ParameterContainer $params Route parameters.
   */
  public function deleteMethod(ParameterContainer $params)
  {
    $userStore = $this->getModel('UserStore');
    $user = $userStore->findById($params->get('id'));

    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    $userStore->delete($user);
  }
}
