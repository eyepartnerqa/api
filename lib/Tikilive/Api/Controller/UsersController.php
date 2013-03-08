<?php

namespace Tikilive\Api\Controller;

use Tikilive\Application\ParameterContainer;
use Tikilive\Controller\AbstractController;
use Tikilive\Exception\Http\BadRequestException;
use Tikilive\Exception\Http\NotFoundException;
use Tikilive\Exception\Validation\ValidationException;
use Tikilive\Http\JsonResponse;

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
    $orderBy   = $request->get('order_by', 'id');
    $direction = $request->get('direction', 'ASC');

    // Retrieve data from model.
    $userModel = $this->getModel('UserModel');
    try {
      $users = $userModel->findAllActive($offset, $limit, $orderBy, $direction);
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
      'total'  => $userModel->countAllActive()
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
    $userModel = $this->getModel('UserModel');

    $user = $userModel->findById($id);

    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    if ($user->getStatus() === 'disabled') {
      throw new NotFoundException('User is no longer available.');
    }

    $response = $user->toArray();

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

    $userModel = $this->getModel('UserModel');
    try {
      $userId = $userModel->insert($user);
    } catch(ValidationException $e) {
      throw new BadRequestException($e->getMessage(), $e->getErrors(), $e);
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
    $userModel = $this->getModel('UserModel');
    $user = $userModel->findById($params->get('id'));

    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    $request = $this->get('request');

    $user->setUsername($request->post('username', $user->getUsername()));
    $user->setEmail($request->post('email', $user->getEmail()));
    $user->setStatus($request->post('status', $user->getStatus()));

    try {
      $userId = $userModel->update($user);
    } catch(ValidationException $e) {
      throw new BadRequestException($e->getMessage(), $e->getErrors(), $e);
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
    $userModel = $this->getModel('UserModel');
    $user = $userModel->findById($params->get('id'));

    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    $userModel->delete($user->getId());
  }
}
