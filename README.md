# swooleAPI

### Инструкция по запуску приложения (при локально установленном фреймворке)

В папке, где хотите создать приложение, создайте файл `composer.json` со следующим содержимым:

```json
{
    "name": "имя/вашего/проекта",
    "description": "Описание вашего проекта",
    "type": "project",
    "require": {
        "php": ">=8.0",
        "ext-swoole": ">=4.8.0",
        "max/swoole-api-framework": "dev-main"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/"
        }
    },
    "repositories": [
        {
            "type": "path",
            "url": "путь/к/папке/с/фреймворком", 
            "options": {
                "symlink": true
            }
        }
    ],
    "minimum-stability": "dev",
    "scripts": {
        "swoole-api": "swoole-api"
    }
}

```

И выполните команду:

```bash
composer install
```

Далее выполните скрипт, создающий структуру проекта (в появляющихся подсказках следуйте инструкции):

```bash
composer swoole-api new .
```

После создания проекта вы можете проверить его следущим образом:

```bash
composer install
php ./public/index.php
```
