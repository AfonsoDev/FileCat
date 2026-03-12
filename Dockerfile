FROM php:8.2-fpm

# Instala Nginx e Supervisor (para rodar Nginx + PHP-FPM no mesmo container)
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
    && rm -rf /var/lib/apt/lists/*

# Copia a config do Nginx
COPY nginx.conf /etc/nginx/conf.d/default.conf
# Remove o site padrão do Nginx
RUN rm -f /etc/nginx/sites-enabled/default

# Copia a config do Supervisor (gerencia Nginx + PHP-FPM)
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Define o diretório de trabalho
WORKDIR /var/www/html

# Copia os arquivos do projeto
COPY . /var/www/html/

# Ajusta permissões para www-data (usuário do Nginx/PHP-FPM)
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

