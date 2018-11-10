<?php


namespace B24Process;

use GuzzleHttp\Client;
use Monolog\Logger;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Handler\StreamHandler;

class BotController implements ControllerProviderInterface
{
    protected $app;

    public function connect(Application $app) {
        $this->app = $app;
        $controllers = $app['controllers_factory'];
        $controllers->post('/', array($this, 'indexAction'));

        return $controllers;
    }
    public function indexAction(Application $app, Request $request)
    {
        $log = new Logger('bot');
        $log->pushHandler(new StreamHandler($app['app_path'] . '/log/log_bot.txt'));
        $request = file_get_contents('php://input');
        $hook = json_decode($request,true);
        $log->info('Hook', [$hook]);

        return json_encode(['ok' => true, 'hook' => $hook]);

    }

}