<?php

use Tikilive\Application\Application;
use Tikilive\Application\Container;
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
  $container->register('exception.handler', function(Container $container) {
    return function(\Exception $e) use ($container) {

      if ($e instanceOf HttpException) {
        $statusCode  = $e->getCode();
        $headers     = $e->getHeaders();
        $message     = $e->getMessage();
      } else {
        $statusCode  = 500;
        $headers     = array();
        $message     = 'Internal Server Error';
      }

      $response = new JsonResponse(null, $statusCode, $headers);
      $response->setReason($message);

      if ($e instanceOf BadRequestException) {
        $response->setCustom('errors', $e->getErrors());
      }

      $debug = $container->get('config')->get('application', 'debug', false);
      if ($debug) {
        $exception = array(
          'type'    => get_class($e),
          'message' => $e->getMessage(),
          'file'    => $e->getFile(),
          'line'    => $e->getLine(),
          'trace'   => $e->getTrace()
        );
        $previous = $e->getPrevious();
        if ($previous) {
          $exception['previous'] = array(
            'type'    => get_class($previous),
            'message' => $previous->getMessage(),
            'file'    => $previous->getFile(),
            'line'    => $previous->getLine(),
            'trace'   => $previous->getTrace()
          );
        }
        $response->setCustom('exception', $exception);
      }

      return $response;
    };
  });

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
