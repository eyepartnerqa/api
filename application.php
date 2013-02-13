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
        $response->setCustom('exception', array(
          'type'    => get_class($e),
          'message' => $e->getMessage(),
          'file'    => $e->getFile(),
          'line'    => $e->getLine(),
          'trace'   => $e->getTrace()
        ));
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

  $router = $this->get('router');

  /**
   * Application is ready at this stage.
   */
  return $app;
};
