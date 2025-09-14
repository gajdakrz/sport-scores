FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    nginx git unzip libpq-dev libzip-dev zip curl \
    && docker-php-ext-install pdo pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Root katalogu spójny z volume
WORKDIR /var/www/html

# Nginx config wbudowany
RUN echo 'server {\n\
    listen 80;\n\
    server_name localhost;\n\
    root /var/www/html/public;\n\
    index index.php index.html;\n\
\n\
    location / {\n\
        try_files $uri /index.php$is_args$args;\n\
    }\n\
\n\
    location ~ \.php$ {\n\
        include fastcgi_params;\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;\n\
        fastcgi_param DOCUMENT_ROOT $document_root;\n\
    }\n\
\n\
    location ~ /\.ht {\n\
        deny all;\n\
    }\n\
}' > /etc/nginx/sites-available/default

RUN ln -sf /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default

# expose port
EXPOSE 80

# start PHP-FPM i Nginx
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
