<?php

// Setup Composer autoloader.
require __DIR__ . '/../../../vendor/autoload.php';

// Initialise the config service.
$dirs = array(
__DIR__ . '/../config/',
__DIR__ . '/../../../config',
__DIR__ . '/../../../config/api'
);

$config = new \Tikilive\Config\Config(new \Tikilive\Config\Loader\FileLoader($dirs));
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $config->get('docs', 'title', 'TikiLIVE'); ?> API documentation</title>
    <link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet' type='text/css'/>
    <link href='css/hightlight.default.css' media='screen' rel='stylesheet' type='text/css'/>
    <link href='css/screen.css' media='screen' rel='stylesheet' type='text/css'/>
    <script src='lib/jquery-1.8.0.min.js' type='text/javascript'></script>
    <script src='lib/jquery.slideto.min.js' type='text/javascript'></script>
    <script src='lib/jquery.wiggle.min.js' type='text/javascript'></script>
    <script src='lib/jquery.ba-bbq.min.js' type='text/javascript'></script>
    <script src='lib/handlebars-1.0.rc.1.js' type='text/javascript'></script>
    <script src='lib/underscore-min.js' type='text/javascript'></script>
    <script src='lib/backbone-min.js' type='text/javascript'></script>
    <script src='lib/swagger.js' type='text/javascript'></script>
    <script src='swagger-ui.js' type='text/javascript'></script>
    <script src='lib/highlight.7.3.pack.js' type='text/javascript'></script>

    <script type="text/javascript">
	$(function () {
	    window.swaggerUi = new SwaggerUi({
                discoveryUrl:"http://<?php echo $config->get('docs', 'host', 'localhost'); ?>/api/resources/api-docs.json",
                apiKeyName:"token",
                apiKey:"oauth-token",
                dom_id:"swagger-ui-container",
                supportHeaderParams: false,
                supportedSubmitMethods: ['get', 'post'],
                docExpansion: 'full',
                onComplete: function(swaggerApi, swaggerUi){
                    if(console) {
                        console.log("Loaded SwaggerUI")
                        console.log(swaggerApi);
                        console.log(swaggerUi);
                    }
                    $('pre code').each(function(i, e) {hljs.highlightBlock(e)});
                },
                onFailure: function(data) {
                    if(console) {
                        console.log("Unable to Load SwaggerUI");
                        console.log(data);
                    }
                },
                docExpansion: "none"
            });

            window.swaggerUi.load();
        });

    </script>
</head>

<body>
<div id='header'>
    <div class="swagger-ui-wrap">
        <a id="logo" href="http://<?php echo $config->get('docs', 'host', 'localhost'); ?>/api/docs/"><?php echo $config->get('docs', 'title', 'TikiLIVE'); ?></a>

        <form id='api_selector'>
            <div class='input'><input placeholder="http://example.com/api" id="input_baseUrl" name="baseUrl"
                                      type="text"/></div>
            <div class='input'><input placeholder="api_key" id="input_apiKey" name="apiKey" type="text"/></div>
            <div class='input'><a id="explore" href="#">Explore</a></div>
        </form>
    </div>
</div>

<div id="message-bar" class="swagger-ui-wrap">
    &nbsp;
</div>

<div id="swagger-ui-container" class="swagger-ui-wrap">

</div>

</body>

</html>
