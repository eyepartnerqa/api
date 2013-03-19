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
 * The API interface to Channel collection.
 *
 * @SWG\Resource(
 *   apiVersion="1.0", swaggerVersion="1.1", resourcePath="/channels",
 *   basePath="http://<api.example.org>/api/"
 * )
 */
class ChannelsController extends AbstractController
{
  /**
   * REST API: GET method.
   *
   * Retrieves all channels or a single channel if a specific channel ID is requested.
   *
   * @param ParameterContainer $params Route parameters.
   */
  public function getMethod(ParameterContainer $params)
  {
    $id = $params->get('id');
    if ($id) {
      return $this->getChannel($id);
    } else {
      return $this->getChannels();
    }
  }

  /**
   * Returns all channels.
   *
   * @return array An array of channels.
   *
   * @SWG\Api(
   *   path="/channels",
   *   description="Operations on a specific channel",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="GET",
   *       summary="Get all active channels",
   *       notes="Returns informations about all active channels",
   *       nickname="ChannelsController_getChannels",
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
   *           @SWG\AllowableValues(valueType="LIST", values="['id','name']")
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
  protected function getChannels()
  {
    // Reqeust parameters.
    $request = $this->get('request');

    $offset    = (int) $request->get('offset', 0);
    $limit     = (int) $request->get('limit', 30);
    $orderBy   = $request->get('order_by', 'id');
    $direction = $request->get('direction', 'ASC');

    // Retrieve data from model.
    $channelModel = $this->getModel('ChannelModel');
    try {
      $channels = $channelModel->findAllActive($offset, $limit, $orderBy, $direction);
    } catch(\InvalidArgumentException $e) {
      throw new BadRequestException($e->getMessage());
    }

    // Format the response.
    $response = array();
    foreach($channels as $channel) {
      $user = $channel->getUser();
      $response[] = array(
        'id'   => $channel->getId(),
        'name' => $channel->getName(),
        'slug' => $channel->getSlug(),
        'user' => array(
          'id'       => $user->getId(),
          'username' => $user->getUsername()
        )
      );
    }

    // Add pager information.
    $response = new JsonResponse($response);
    $response->setCustom('pager', array(
      'offset' => $offset,
      'limit'  => $limit,
      'total'  => $channelModel->countAllActive()
    ));

    return $response;
  }

  /**
   * Returns a channel.
   *
   * @param int $id The channel ID.
   * @return array An associative array containing channel info.
   *
   * @SWG\Api(
   *   path="/channels/{channelId}",
   *   description="Operations on a specific channel",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="GET",
   *       summary="Get info about a channel",
   *       notes="Returns informations about a specific channel",
   *       nickname="ChannelsController_getChannel",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="channelId", description="Channel ID to fetch",
   *           paramType="path", required="true", allowMultiple="false", dataType="int"
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="404",
   *           reason="Channel was not found or no longer available"
   *         )
   *       )
   *     )
   *   )
   * )
   */
  protected function getChannel($id)
  {
    $channelModel = $this->getModel('ChannelModel');

    $channel = $channelModel->findById($id);

    if ($channel === null) {
      throw new NotFoundException('Channel does not exist.');
    }

    if ($channel->getStatus() === 'disabled') {
      throw new NotFoundException('Channel is no longer available.');
    }

    $user = $channel->getUser();
    $response = array(
      'id'          => $channel->getId(),
      'name'        => $channel->getName(),
      'slug'        => $channel->getSlug(),
      'description' => $channel->getDescription(),
      'created'     => $channel->getCreated(),
      'user' => array(
        'id'       => $user->getId(),
        'username' => $user->getUsername()
      )
    );

    return $response;
  }

  /**
   * REST API: POST method.
   *
   * Creates a new channel.
   *
   * @param ParameterContainer $params Route parameters.
   *
   * @SWG\Api(
   *   path="/channels",
   *   description="Operations on a specific channel",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="POST",
   *       summary="Create a new channel",
   *       notes="Creates a new channel",
   *       nickname="ChannelsController_postMethod",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="user_id", description="The parent user ID",
   *           paramType="form", required="true", allowMultiple="false", dataType="int"
   *         ),
   *         @SWG\Parameter(
   *           name="name", description="The channel name",
   *           paramType="form", required="true", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="description", description="The channel description",
   *           paramType="form", required="false", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="status", description="The channel status",
   *           paramType="form", required="false", allowMultiple="false",
   *           dataType="string", defaultValue="enabled",
   *           @SWG\AllowableValues(valueType="LIST", values="['enabled', 'disabled']")
   *         ),
   *         @SWG\Parameter(
   *           name="published", description="The channel publishing status",
   *           paramType="form", required="false", allowMultiple="false",
   *           dataType="string", defaultValue="published",
   *           @SWG\AllowableValues(valueType="LIST", values="['published', 'unpublished']")
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

    $userModel = $this->getModel('UserModel');
    $user = $userModel->findById($request->post('user_id'));
    if ($user === null) {
      throw new NotFoundException('User does not exist.');
    }

    $channel = $this->getModel('ChannelEntity');
    $channel->setName($request->post('name'));
    $channel->setDescription($request->post('description'));
    $channel->setStatus($request->post('status', 'enabled'));
    $channel->setPublished($request->post('published', 'published'));
    $channel->setUser($user);

    $channelModel = $this->getModel('ChannelModel');
    try {
      $channelId = $channelModel->insert($channel);
    } catch(ValidationException $e) {
      throw new BadRequestException($e->getMessage(), $e->getErrors());
    }

    return array(
      'id' => $channelId
    );
  }

  /**
   * REST API: PUT method.
   *
   * Updates an existing channel.
   *
   * @param ParameterContainer $params Route parameters.
   *
   * @SWG\Api(
   *   path="/channels/{channelId}",
   *   description="Operations on a specific channel",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="PUT",
   *       summary="Update an existing channel",
   *       notes="Updates an existing channel",
   *       nickname="ChannelsController_putMethod",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="channelId", description="Channel ID to update",
   *           paramType="path", required="true", allowMultiple="false", dataType="int"
   *         ),
   *         @SWG\Parameter(
   *           name="name", description="The channel name",
   *           paramType="form", required="false", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="description", description="The channel description",
   *           paramType="form", required="false", allowMultiple="false", dataType="string"
   *         ),
   *         @SWG\Parameter(
   *           name="status", description="The channel status",
   *           paramType="form", required="false", allowMultiple="false", dataType="string",
   *           @SWG\AllowableValues(valueType="LIST", values="['enabled', 'disabled']")
   *         ),
   *         @SWG\Parameter(
   *           name="published", description="The channel publishing status",
   *           paramType="form", required="false", allowMultiple="false", dataType="string",
   *           @SWG\AllowableValues(valueType="LIST", values="['published', 'unpublished']")
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
    $channelModel = $this->getModel('ChannelModel');
    $channel = $channelModel->findById($params->get('id'));

    if ($channel === null) {
      throw new NotFoundException('Channel does not exist.');
    }

    $request = $this->get('request');

    $channel->setName($request->post('name', $channel->getName()));
    $channel->setDescription($request->post('description', $channel->getDescription()));
    $channel->setStatus($request->post('status', $channel->getStatus()));
    $channel->setPublished($request->post('published', $channel->getPublished()));

    try {
      $channelId = $channelModel->update($channel);
    } catch(ValidationException $e) {
      throw new BadRequestException($e->getMessage(), $e->getErrors());
    }
  }

  /**
   * REST API: DELETE method.
   *
   * Deletes an existing channel.
   *
   * @param ParameterContainer $params Route parameters.
   *
   * @SWG\Api(
   *   path="/channels/{channelId}",
   *   description="Operations on a specific channel",
   *   @SWG\Operations(
   *     @SWG\Operation(
   *       httpMethod="DELETE",
   *       summary="Delete an existing channel",
   *       notes="Deletes an existing channel",
   *       nickname="ChannelsController_putMethod",
   *       @SWG\Parameters(
   *         @SWG\Parameter(
   *           name="channelId", description="Channel ID to delete",
   *           paramType="path", required="true", allowMultiple="false", dataType="int"
   *         )
   *       ),
   *       @SWG\ErrorResponses(
   *         @SWG\ErrorResponse(
   *           code="404",
   *           reason="Channel was not found"
   *         )
   *       )
   *     )
   *   )
   * )
   */
  public function deleteMethod(ParameterContainer $params)
  {
    $channelModel = $this->getModel('ChannelModel');
    $channel = $channelModel->findById($params->get('id'));

    if ($channel === null) {
      throw new NotFoundException('Channel does not exist.');
    }

    $channelModel->delete($channel->getId());
  }
}
