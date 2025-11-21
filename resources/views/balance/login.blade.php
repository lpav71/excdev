<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Вход - Балансы пользователей</title>
    @vite(['resources/js/balance.js'])
</head>
<body>
    <div id="login-app"></div>
</body>
</html>

