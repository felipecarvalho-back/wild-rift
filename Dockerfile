FROM dunglas/frankenphp:1.2-php8.5

# Instalar o instalador de extensões do PHP da comunidade
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Instalar as extensões do PHP necessárias para o Laravel e Laravel Octane
RUN install-php-extensions pcntl posix opcache gd zip pdo_mysql intl redis bcmath

# Configurar o arquivo php.ini padrão para desenvolvimento
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Instalar o Composer na imagem
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Definir o diretório de trabalho
WORKDIR /app

# Garantir que o container possa rodar comandos do artisan e possua permissões adequadas
RUN chown -R www-data:www-data /app

# Expõe as portas padrão
EXPOSE 8000 80 443

# Comando padrão para iniciar o Laravel Octane com FrankenPHP
ENTRYPOINT ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]
