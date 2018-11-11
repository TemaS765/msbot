# msbot
Тест получение сообщений из whatsapp через chat-api обработка и передача в ms bot framework

#Установка
composer install

создаем файл конфигураций (src/config_dev.yml) на основе src/config_dev_blank.yml

#API
/hook/set - устанавливает webhook для chat-api
/hook/put - обрабатывает hook
/bot - для тестирования получает все,что приходит боту
