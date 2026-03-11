FROM php:8.2-apache

# Habilita o mod_rewrite do Apache (necessário para os .htaccess do FileCat)
RUN a2enmod rewrite

# Instala extensões necessárias, se houver (o FileCat MVP não exige outras extensões além das padrão)
# RUN docker-php-ext-install ...

# Define o ServerName para evitar avisos
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto para o diretório raiz do servidor
COPY . /var/www/html/

# Ajusta as permissões para o usuário padrão do Apache (www-data)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/auth /var/www/html/messages

EXPOSE 80
