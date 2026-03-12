FROM php:8.2-apache

# Habilita o mod_rewrite do Apache (necessário para os .htaccess do FileCat)
RUN a2enmod rewrite

# Instala extensões necessárias, se houver (o FileCat MVP não exige outras extensões além das padrão)
# RUN docker-php-ext-install ...

# Define o ServerName para evitar avisos
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

# Habilita AllowOverride All para que os .htaccess funcionem corretamente
# e libera acesso PHP a todos os subdiretórios (auth/, api/, etc.)
RUN sed -i 's|<Directory /var/www/html/>|<Directory /var/www/html/>\n\tAllowOverride All|' /etc/apache2/sites-available/000-default.conf || true
RUN printf '<Directory /var/www/html/>\n\tOptions -Indexes +FollowSymLinks\n\tAllowOverride All\n\tRequire all granted\n</Directory>\n' \
    > /etc/apache2/conf-available/filecat.conf \
    && a2enconf filecat

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto para o diretório raiz do servidor
COPY . /var/www/html/

# Ajusta as permissões para o usuário padrão do Apache (www-data)
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
