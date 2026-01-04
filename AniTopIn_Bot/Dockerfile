FROM php:8.1-apache

# MySQL kengaytmalarini o'rnatish
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Serverga fayllarni nusxalash
COPY . /var/www/html/

# AGAR PAPKALAR YO'Q BO'LSA, ULARNI YARATISH VA RUXSAT BERISH
RUN mkdir -p /var/www/html/admin /var/www/html/step /var/www/html/tugma && \
    chmod -R 777 /var/www/html/admin /var/www/html/step /var/www/html/tugma

EXPOSE 80
