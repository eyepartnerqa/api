<?php

use Tikilive\Application\Application;
use Tikilive\Http\JsonResponse;
use Tikilive\Exception\Http\AbstractException as HttpException;

return function() {

  $app = new Application();

  /**
   * Services.
   */

  // Initialise the config service.
  $app->set('config', $app->factory('config.factory', array(
    __DIR__ . '/config/',
    __DIR__ . '/../../config',
    __DIR__ . '/../../config/api'
  )));

  /**
   * Application handlers.
   */

  // Custom exception handler.
  $app->register('exception.handler', function($app) {
    return function(\Exception $e) use ($app) {

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
      $response->setMessage($message);

      $debug = $app->get('config')->get('application', 'debug', false);
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
  $app->register('response.handler', function($app) {
    return function($response) {
      return new JsonResponse($response);
    };
  });

  /**
   * Routing
   */

  $router = $app->get('router');

  /**
   * Application is ready at this stage.
   */
  return $app;
};
