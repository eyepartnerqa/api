<?php

namespace Tikilive\Api\Controller;

use Tikilive\Application\ParameterContainer;
use Tikilive\Controller\AbstractController;
use Tikilive\Exception\Http\BadRequestException;
use Tikilive\Exception\Http\NotFoundException;
use Tikilive\Exception\Validation\ValidationException;
use Tikilive\Http\JsonResponse;

/**
 * The API interface to Channel collection.
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
