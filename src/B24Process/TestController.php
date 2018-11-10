<?php


namespace B24Process;

use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class TestController implements ControllerProviderInterface
{
    public function connect(Application $app) {
        $controllers = $app['controllers_factory'];
        $controllers->match('/', array($this, 'testPage'));
        return $controllers;
    }
    public function testPage(Application $app, Request $request) {
        return new ControllerResult('testpage', ['test' => 'Hello World']);
    }



}