<?php

namespace Tikilive\Api\Controller;

use Tikilive\Application\ParameterContainer;
use Tikilive\Controller\AbstractController;

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
   * @param array An array of users.
   */
  protected function getUsers()
  {
    $users[] = array(
      'id'       => 100,
      'username' => 'username1'
    );

    $users[] = array(
      'id'       => 101,
      'username' => 'username2'
    );

    return $users;
  }

  /**
   * Returns a user.
   *
   * @param int $id The user ID.
   * @return array An associative array containing user info.
   */
  protected function getUser($id)
  {
    return array(
      'id'       => $id,
      'username' => 'username1'
    );
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
    $userId = rand(1000, 1100);

    return array(
      'id'   => $userId,
      'link' => $this->urlFor(
                  'resource',
                  array('controller' => 'users', 'id' => $userId),
                  true
                )
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
    return null;
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
    return null;
  }
}
