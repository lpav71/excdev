# Приложение "Балансы пользователей"

## Установка и настройка

1. Установите зависимости:
```bash
composer install
npm install
```

2. Настройте `.env` файл с настройками базы данных (PostgreSQL или MySQL)

3. Запустите миграции:
```bash
php artisan migrate
```

4. Соберите фронтенд ресурсы:
```bash
npm run build
# или для разработки:
npm run dev
```

## Использование

### Создание пользователя

```bash
php artisan user:add
```

Или с параметрами:
```bash
php artisan user:add --name="Иван Иванов" --email="ivan@example.com" --login="ivan" --password="password123"
```

### Проведение операций по балансу

**Без очереди (синхронно):**
```bash
php artisan balance:operation --login="ivan" --type="deposit" --amount="1000" --description="Начисление бонусов"
php artisan balance:operation --login="ivan" --type="withdrawal" --amount="500" --description="Оплата услуг"
```

**С очередью (асинхронно):**
```bash
php artisan balance:operation --login="ivan" --type="deposit" --amount="1000" --description="Начисление бонусов" --queue
```

Для обработки очереди запустите:
```bash
php artisan queue:work
```

### Веб-интерфейс

- **Логин:** `/balance/login`
- **Главная страница:** `/balance/dashboard` (показывает баланс и последние 5 операций, обновляется каждые 5 секунд)
- **История операций:** `/balance/operations` (с поиском и сортировкой)

## Структура базы данных

- **users** - пользователи (добавлено поле `login`)
- **balances** - балансы пользователей
- **operations** - история операций (начисление/списание)

## Технологии

- PHP 8, Laravel 12
- PostgreSQL/MySQL
- Vue 3
- Bootstrap 5
- SCSS
- Laravel Queues (для асинхронной обработки операций)

