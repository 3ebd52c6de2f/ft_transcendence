FROM php:8.2-fpm

# git & unzip => se necesitan para instalar google/apiclient (two_factorAuthentif-ication)
RUN apt-get update && apt-get install -y \
    libsqlite3-dev  \ 
	git  \ 
	unzip \ 
	&& docker-php-ext-install pdo pdo_sqlite

# Instalar Composer => two_fa ; Composer es un gestor de dependencias para PHP. 
# Es la herramienta estándar en el ecosistema de PHP para manejar librerías de terceros. 
# Descarga las librerias que solicitas a través de u archivo composer.json
# También todos los otras librerias de los que estos dependen. 
# Las organiza en una carpeta llamada vendor y añade un índice vendor/autoload.php, 
# que al ser incluido en un script le dice a PHP dónde encontrar cualquier dependencia relacionada con las librerias externas.
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html