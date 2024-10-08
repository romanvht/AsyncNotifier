# Async notifier

Очередь сообщений на ReactPHP + Redis для отправки уведомлений через различные каналы (электронная почта, Telegram). 
Включает в себя обработчик очереди и API для добавления задач в очередь.

## Установка

1. Клонируйте репозиторий:
    ```sh
    git clone https://github.com/romanvht/AsyncNotifier.git
    cd AsyncNotifier
    ```

2. Установите зависимости с помощью Composer:
    ```sh
    composer install
    ```

3. Создайте файл `.env` в корне проекта, взяв за основу файл `.env.template`:
    ```sh
    cp .env.template .env
    ```

4. Убедитесь, что у вас установлен и настроен Redis, так как проект использует его для очередей.

## Использование

### Настройка API

Настройте ваш веб-сервер (например, Apache или Nginx) для работы с папкой `public` в качестве корневого каталога. После этого API будет доступно по вашему домену или IP-адресу.

### Пример запроса к API

Отправьте POST-запрос на `http://ваш-домен` с телом запроса в формате JSON для электронной почты:
```json
{
    "type": "email",
    "data": {
        "to": "recipient@example.com",
        "subject": "Test Email",
        "body": "This is a test email."
    }
}
```

Отправьте POST-запрос на `http://ваш-домен` с телом запроса в формате JSON для Telegram:
```json
{
    "type": "telegram",
    "data": {
        "chat_id": "your_chat_id",
        "message": "This is a test message."
    }
}
```

### Запуск воркера

Воркер обрабатывает задачи из очереди. Чтобы запустить воркера, выполните следующую команду:
```sh
php worker.php
```

## Логи

Проект использует Monolog для логирования. Логи записываются в файлы в директории `logs`. Также логи выводятся в стандартный вывод (stdout).

## Зависимости

- PHP 7.4 или выше
- Composer
- Redis
- PHPMailer
- GuzzleHttp
- Monolog
- ReactPHP