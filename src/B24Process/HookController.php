<?php


namespace B24Process;

use GuzzleHttp\Client;
use Monolog\Logger;
use Silex\Application;
use Silex\Api\ControllerProviderInterface;
use Symfony\Component\HttpFoundation\Request;

class HookController implements ControllerProviderInterface
{
    protected $app;

    /**
     * Регистрация экшенов
     * @param Application $app
     * @return mixed|\Silex\ControllerCollection
     */
    public function connect(Application $app) {
        $this->app = $app;
        $controllers = $app['controllers_factory'];
        $controllers->post('/', array($this, 'indexAction'));
        $controllers->get('/set', array($this, 'setAction'));
        return $controllers;
    }

    /**
     * Экшен для обработки хука
     * @param Application $app
     * @param Request $request
     * @return false|string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function indexAction(Application $app, Request $request) {

        $logger = $app['logger']; /** @var Logger $logger*/
        $request = file_get_contents('php://input');
        $hook = json_decode($request,true);
        $logger->info('Тело hook',[$hook]);

        if($hook) {
            if($messages = $hook['messages']) {
                $result = [];
                $messForBot = $this->paresMessageFromBot($messages);

                foreach ($messForBot as $msg) {
                    $dialogId = null;
                    $text = null;
                    $dialog = $this->getIdDialogByPhone($msg['phone']);

                    if ($dialog) {
                        $dialogId = $dialog['id_dialog'];
                        $text = $msg['text'];
                    } else {
                        $newDialog = $this->createNewDialogBot();
                        $text = "Сообщение из WhatsApp, номер телефона {$msg['phone']}\n\n{$msg['text']}";
                        $dialogId = $newDialog['conversationId'] ? $newDialog['conversationId'] : null;

                        if(!empty($dialogId)) {
                            $this->setDialog($dialogId, $msg['phone']);
                        } else {
                            $result[] = ['message' => $msg['phone'],'error' => 'Не удалось создать диалог с ботом'];
                            continue;
                        }
                    }

                   $result[] = $this->sendMessageBot($dialogId, $msg['phone'], $text);
                }

                $logger->info('Результат отправки',[$result]);

                return json_encode(['ok' => true, 'hook' => $hook]);

            } else {
                return json_encode(['ok' => false, 'hook' => $hook]);
            }

        } else {
            return json_encode(['ok' => false, 'hook' => $hook]);
        }


    }

    /**
     * Экшен установки хука для chat-api
     * @param Application $app
     * @return \Psr\Http\Message\StreamInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function setAction(Application $app) {

        $client = new Client();
        $res = $client->request('GET', "https://eu24.chat-api.com/instance15458/webhook?".
            "webhookUrl={$app['chat.api']['webhookUrl']}&".
        "token={$app['chat.api']['token']}");
        return $res->getBody();
    }

    /**
     * Отправляем сообщение Боту
     * @param string $dialogId ID диалога
     * @param string $phone Номер телефона
     * @param string $text Сообщение
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function sendMessageBot($dialogId, $phone, $text) {

        $body = [
            'type' => 'message',
            'from' => [
                'id' => $phone
            ],
            'text' => $text
        ];

        $client = new Client();
        $res = $client->request('POST', "https://directline.botframework.com/v3/directline/conversations/{$dialogId}/activities",
            [
            'headers' => [
                'Authorization' => 'Bearer '. $this->app['bot']['secret']
            ],
            'json' => $body
            ]);
        return json_decode($res->getBody(),true);
    }

    /**
     * Вывод информации о диалоге по номеру телефона
     * @param string $phone Номер телефона
     * @return mixed
     */
    protected function getIdDialogByPhone($phone) {
        $db = new DB($this->app['db.options']);

        $result = $db->query('SELECT * FROM `dialog` WHERE `phone` = :phone limit 1;',['phone' => $phone]);
        return $result[0];
    }

    /**
     * Добавляем информацию о диалоге в базу
     * @param string $dialogId ID диалога
     * @param string $phone ноиер телефона
     * @return bool
     */
    protected function setDialog($dialogId, $phone) {
        $db = new DB($this->app['db.options']);
        $result = $db->execute('INSERT INTO `dialog` (`id_dialog`, `phone`) VALUES (:id_dialog, :phone);',['id_dialog'=>$dialogId, 'phone' => $phone]);
        return $result;
    }

    /**
     * Создаем диалог с ботом
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function createNewDialogBot() {

        $client = new Client();
        $res = $client->request('POST', "https://directline.botframework.com/v3/directline/conversations",[
            'headers' => [
                'Authorization' => 'Bearer '. $this->app['bot']['secret']
            ]
        ]);
        return json_decode($res->getBody()->getContents(), true);
    }

    /**
     * Разбор сообшений полученых от chat-api
     * @param  array $messages Массив сообщений полученных от chat-api
     * @return array Массив подгатовленных сообщений для бота
     */
    protected function paresMessageFromBot($messages) {
        $colMsg = count($messages);
        $msg = [];
        for($i = 0; $i < $colMsg; $i++) {
            $msg[$i]['phone'] = preg_replace('~[^\d]~', '', $messages[$i]['author']);
            $msg[$i]['text'] = $messages[$i]['body'];
        }

        return $msg;
    }
}
