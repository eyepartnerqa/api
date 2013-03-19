<?php

namespace Tikilive\Api\Controller;

use Swagger\Annotations as SWG;
use Tikilive\Application\ParameterContainer;
use Tikilive\Controller\AbstractController;
use Tikilive\Exception\Http\BadRequestException;
use Tikilive\Exception\Http\NotFoundException;
use Tikilive\Exception\Validation\ValidationException;
use Tikilive\Http\JsonResponse;

/**
 * The API interface to User collection.
 *
 * @SWG\Resource(
 *   apiVersion="1.0", swaggerVersion="1.1", resourcePath="/users",
 *   basePath="http://<api.example.org>/api/"
 * )
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
   *
   * @SWG\Api(
   *   path="/users",
   *   description="Operations on a specific user",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="GET",
   *       summary="Get all active users",
   *       notes="Returns informations about all active users",
   *       nickname="UsersController_getUsers",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="offset", description="The offset of the first row",
   *           paramType="query", required="false", allowMultiple="false",
   *           dataType="int", defaultValue="0"
   *         ),
   *         @SWG\Parameter(
   *           name="limit", description="The number of rows to return",
   *           paramType="query", required="false", allowMultiple="false",
   *           dataType="int", defaultValue="0",
   *           @SWG\AllowableValues(valueType="RANGE", min="0", max="100")
   *         ),
   *         @SWG\Parameter(
   *           name="order_by", description="Order by keyword",
   *           paramType="query", required="false", allowMultiple="false",
   *           dataType="string", defaultValue="id",
   *           @SWG\AllowableValues(valueType="LIST", values="['id','username']")
   *         ),
   *         @SWG\Parameter(
   *           name="direction", description="Order direction",
   *           paramType="query", required="false", allowMultiple="false",
   *           dataType="string", defaultValue="asc",
   *           @SWG\AllowableValues(valueType="LIST", values="['ASC','DESC']")
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="400",
   *           reason="Invalid parameters"
   *         )
   *       )
   *     )
   *   )
   * )
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
   *
   * @SWG\Api(
   *   path="/users/{userId}",
   *   description="Operations on a specific user",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="GET",
   *       summary="Get info about a user",
   *       notes="Returns informations about a specific user",
   *       nickname="UsersController_getUser",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="userId", description="User ID to fetch",
   *           paramType="path", required="true", allowMultiple="false", dataType="int"
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="404",
   *           reason="User was not found or the profile is no longer available"
   *         )
   *       )
   *     )
   *   )
   * )
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
   * @return array An associative array containing the generated user ID.
   *
   * @SWG\Api(
   *   path="/users",
   *   description="Operations on a specific user",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="POST",
   *       summary="Create a new user",
   *       notes="Creates a new user",
   *       nickname="UsersController_postMethod",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="username", description="The username",
   *           paramType="form", required="true", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="email", description="The email address of the user",
   *           paramType="form", required="true", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="status", description="The user status",
   *           paramType="form", required="false", allowMultiple="false",
   *           dataType="string", defaultValue="enabled",
   *           @SWG\AllowableValues(valueType="LIST", values="['enabled', 'disabled']")
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="400",
   *           reason="Some fields did not pass validation"
   *         )
   *       )
   *     )
   *   )
   * )
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
   *
   * @SWG\Api(
   *   path="/users/{userId}",
   *   description="Operations on a specific user",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="PUT",
   *       summary="Update an existing user",
   *       notes="Updates an existing user",
   *       nickname="UsersController_putMethod",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="userId", description="User ID to update",
   *           paramType="path", required="true", allowMultiple="false", dataType="int"
   *         ),
   *         @SWG\Parameter(
   *           name="username", description="The username",
   *           paramType="form", required="false", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="email", description="The email address of the user",
   *           paramType="form", required="false", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="status", description="The user status",
   *           paramType="form", required="false", allowMultiple="false", dataType="string",
   *           @SWG\AllowableValues(valueType="LIST", values="['enabled', 'disabled']")
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="400",
   *           reason="Some fields did not pass validation"
   *         )
   *       )
   *     )
   *   )
   * )
   */
  public function putMethod(ParameterContainer $params)
  {
    $userModel = $this->getModel('UserModel');
    $user = $userModel->findById($params->get('id'));

    if ($user === null) {
      throw new NotFoundException('User was not found.');
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
   *
   * @SWG\Api(
   *   path="/users/{userId}",
   *   description="Operations on a specific user",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="DELETE",
   *       summary="Delete an existing user",
   *       notes="Deletes an existing user",
   *       nickname="UsersController_putMethod",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="userId", description="User ID to delete",
   *           paramType="path", required="true", allowMultiple="false", dataType="int"
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="404",
   *           reason="User was not found"
   *         )
   *       )
   *     )
   *   )
   * )
   */
  public function deleteMethod(ParameterContainer $params)
  {
    $userModel = $this->getModel('UserModel');
    $user = $userModel->findById($params->get('id'));

    if ($user === null) {
      throw new NotFoundException('User was not found.');
    }

    $userModel->delete($user->getId());
  }
}
