<?php

use Tikilive\Application\Application;
use Tikilive\Application\Container;
use Tikilive\Application\Provider\JsonExceptionHandlerProvider;
use Tikilive\Http\JsonResponse;
use Tikilive\Exception\Http\AbstractException as HttpException;
use Tikilive\Exception\Http\BadRequestException;

return function() {

  $container = new Container();
  $app = new Application($container);

  /**
   * Services.
   */

  // Initialise the config service.
  $container->set('config', $container->factory('config.factory', array(
    __DIR__ . '/config/',
    __DIR__ . '/../../config',
    __DIR__ . '/../../config/api'
  )));

  /**
   * Application handlers.
   */

  // Custom exception handler.
  $container->registerProvider(new JsonExceptionHandlerProvider(), 'exception.handler');

  // Custom response handler.
  $container->register('response.handler', function(Container $container) {
    return function($response) {
      return new JsonResponse($response);
    };
  });

  /**
   * Routing
   */

  $router = $container->get('router');

  // Default resource.
  $router->map('resource', '/:controller/:id')
         ->setmethods(array('GET', 'PUT', 'DELETE'))
         ->setRequirements(array(
             'controller' => '[a-z0-9_-]+',
             'id' => '[1-9]\d*'
           ));

  // Default collection.
  $router->map('collection', '/:controller')
         ->setMethods(array('POST', 'GET'))
         ->setRequirement('controller', '[a-z0-9_-]+');

  /**
   * Application is ready at this stage.
   */
  return $app;
};
