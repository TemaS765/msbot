<?php

require_once __DIR__.'/../vendor/autoload.php';

class Application extends Silex\Application
{
    use Silex\Application\TwigTrait;
}


$app = new Application();


$app['app_path'] = __DIR__.'/..';

putenv("APP_ENV=dev");

// Twig
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../src/templates',
    'twig.options' => array('strict_variables' => false),
));

$app['twig'] =$app->extend('twig', function(Twig_Environment $twig, $app) {
    $twig->addGlobal('template_root', '/');
    if(isset($app["view.globals"])) {
        foreach($app["view.globals"] as $key => $value) {
            $twig->addGlobal($key, $value);
        }
    }
    foreach($app["view.additional"] as $key => $value) {
        $twig->addGlobal($key, $value);
    }
    return $twig;
});

$app['view.additional'] = array();
$app['view.add'] = $app->protect(function(Application $app, $key, $value) {
    $app['view.additional'] = $app['view.additional'] + array($key => $value);
});

// Database
$app->register(new \Silex\Provider\DoctrineServiceProvider());


// Logger
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../log/log.txt',
));

// Config
// SHOULD BE THE LAST SERVICE!
$env = getenv('APP_ENV') ?: 'prod';

$app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__."/../src/config_$env.yml"));
$app->register(new \Igorw\Silex\ConfigServiceProvider(__DIR__."/../src/app_settings.yml"));

// END OF SERVICE PROVIDERS REGISTER


$app['template_render'] = $app->protect(function($app, $template_name, $data) {
    /** @var Twig_Environment $twig */
    $twig = $app['twig'];
    if(is_array($template_name)) {
        $templates = $template_name;
    } else {
        $templates = array($template_name.'.twig');
    }
    try {
        $template = $twig->resolveTemplate($templates);
    } catch(Twig_Error_Loader $exc) {
        $template = $twig->createTemplate("");
    }
    return $template->render($data);
});

// Defining view
$app->view(function (\B24Process\ControllerResult $controllerResult) use ($app) {
    return $app['template_render']($app,
        array($controllerResult->getTemplateName().'.twig', 'standalone_page.twig'),
        $controllerResult->getData()
    );
});


$app->mount('test', new B24Process\TestController());
$app->mount('hook', new \B24Process\HookController());
$app->mount('bot', new \B24Process\BotController());

$app->get('/', function() use ($app) {
    return $app->render('index.twig', []);
});

$app->run();