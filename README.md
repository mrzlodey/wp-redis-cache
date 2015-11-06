## Wp Redis Cache

Cache Wordpress using Redis, the fastest way to date to cache Wordpress.

### Requirements
------
* [Wordpress](http://wordpress.org) - CMS framework/blogging system
* [Redis](http://redis.io/) - Key Value in memory caching
* [Predis](https://github.com/nrk/predis) - PHP api for Redis

### Installation 
------
Install Redis, must have root access to your machine. On debian it's as simple as:
```bash
sudo apt-get install redis-server
```
On other systems please refer to the [Redis website](http://redis.io/).

You can install the pecl extension (faster)
```
apt-get install php5-redis
```
If you don't have the pecl extension installed it will default to use [Predis](https://github.com/nrk/predis).

Move the `wp-cache.php` and `predis` to the root/base Wordpress directory. In `wp-cache.php` if you want to use sockets, change `$redis_unix` to `true` and enter the path of your socket in `$redis_sock`.

Nginx virtual host configuration:

<code>server {
    listen 80;

    server_name localhost;
    
    root /usr/share/nginx/html;
    index index.php;

    location /index.php {
        alias /www/codebeer.ru/wordpress/wp-cache.php;
    }

    location / {
        index wp-cache.php;
        try_files $uri $uri/ /wp-cache.php?$args;
    }

    location /wp-admin/ {
        index index.php;
        try_files $uri $uri/ /index.php$args;
    }

    rewrite /wp-admin$ $scheme::/$host$uri/ permanent;
    
    location ~ \.php$ {
        try_files $uri = 404;
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}</code>

### Benchmark
------
Wp Redis Cache
```
Page generated in 0.00117 seconds.
```
