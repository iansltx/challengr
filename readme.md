## Challengr

This application is the system under test for @iansltx's load testing talks. Set it up as you would a normal
Laravel app (Dockerfile provided for convenience/reference), e.g.

```
composer install
php artisan migrate
php artisan passport:install
```

Ready to simulate a fair number of users in the database? `php artisan db:seed` is your friend.

Use the various branches on this repo to see the project at various stages of optimization.
