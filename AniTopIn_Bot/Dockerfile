# PHP va Apache o'rnatilgan rasmiy tasvirdan foydalanamiz
FROM php:8.1-apache

# MySQL bilan ishlash uchun kerakli kengaytmalarni o'rnatamiz
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Apache modulini yoqamiz (URL'larni chiroyli qilish uchun)
RUN a2enmod rewrite

# Loyiha fayllarini serverga nusxalaymiz
COPY . /var/www/html/

# Papkalarga ruxsat beramiz (Bot ma'lumot yozishi uchun juda muhim!)
RUN chmod -R 777 /var/www/html/admin /var/www/html/step /var/www/html/tugma

# Apache portini sozlaymiz
EXPOSE 80