# 🐱 File Cat

> Um gerenciador de arquivos open source, simples, bonito e fácil de instalar.  
> Inspirado no Google Drive e FilGator, mas focado na simplicidade e facilidade de deploy.

![Licença](https://img.shields.io/badge/License-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-777BB4.svg)

**File Cat** é um gerenciador de arquivos self-hosted no navegador. Foi construído para ser incrivelmente leve e rodar em praticamente qualquer servidor sem a necessidade de banco de dados complexos ou processos elaborados de compilação (build). 

## ✨ Principais Funcionalidades

- **Instalação Simples:** Funciona com PHP puro. Sem Node.js, sem npm, sem banco de dados obrigatório (usa JSON/Sistema de Arquivos).
- **Interface Moderna:** UI limpa e responsiva construída com Tailwind CSS.
- **Upload Inteligente:** Suporte a múltiplos arquivos simultâneos e drag & drop na interface.
- **Operações Básicas:** Crie pastas, renomeie, mova e exclua arquivos com facilidade.
- **Segurança Nativa:** Proteção de path traversal, rotas protegidas por Middleware e pastas vitais trancadas via `.htaccess`.
- **Modos de Visualização:** Alterne entre listagem em grade (grid) ou em lista de detalhes.

---

## 🚀 Como Instalar

O File Cat foi desenhado para rodar num servidor web com suporte a PHP 8.0+. Você tem duas opções recomendadas para hospedagem.

### Opção 1: Hospedagem Compartilhada / Servidor Web (Apache/Nginx)

Se você tem uma hospedagem convencional (cPanel) ou um servidor Linux com Apache/Nginx e PHP configurado:

1. Faça o download (arquivo `.zip`) deste repositório ou clone via `git`:
   ```bash
   git clone https://github.com/AfonsoDev/FileCat.git
   ```
2. Mova (ou faça upload via FTP) de todos os arquivos para o diretório público do seu servidor (ex: `/var/www/html` ou `public_html`).
3. Acesse a url de instalação através do seu navegador:
   ```text
   https://seusite.com/FileCat/install.php
   ```
4. Preencha o formulário para criar o usuário administrador (isso gerará automaticamente as pastas e credenciais necessárias).
5. Pronto! Acesse o login e comece a gerenciar seus arquivos.

> **Importante:** Certifique-se de que a extensão `fileinfo` está ativa no PHP e que a pasta raiz do projeto tem as permissões corretas para gravação de arquivos/pastas.

---

### Opção 2: Via Docker Compose (Recomendado para VPS e Localhost)

Se preferir rodar a aplicação em containers Docker, o ambiente já vêm preparado com a imagem oficial do Apache embutindo as configurações de mod_rewrite e permissões.

1. Clone o repositório no seu servidor ou máquina local:
   ```bash
   git clone https://github.com/AfonsoDev/FileCat.git
   cd FileCat
   ```
2. Suba o container rodando:
   ```bash
   docker-compose up -d --build
   ```
3. A aplicação estará ativa na porta `8000`. Acesse para instalar:
   ```text
   http://localhost:8080/install.php
   ```
4. Os arquivos que você fizer upload, assim como as credenciais (`users.json`), persistirão nas pastas correspondentes de sua máquina (`./storage`, `./auth`, `./messages` - mapeamentos de volume que estão no `docker-compose.yml`).

---

## 🛠️ Stack Tecnológica

- **Backend:** PHP 8.0+
- **Frontend:** HTML5, JS Vanilla
- **Estilização:** Tailwind CSS (via CDN na Fase MVP)
- **Armazenamento:** Sistema nativo de arquivos do servidor e arquivos JSON genéricos.
- **Autenticação:** Sessão PHP Segura e `password_hash()` BCRYPT.

## 📄 Licença

Este projeto é desenvolvido e distribuído sob a Licença MIT - livre para usar, modificar e distribuir.
