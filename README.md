# AgroOferta

O AgroOferta é um marketplace de insumos agropecuários. Agricultores podem anunciar excedentes que têm disponíveis, e outros produtores podem encontrar itens de que precisam e negociar diretamente com o vendedor.

## Funcionalidades atuais

- Cadastro, login e gerenciamento de conta.
- Publicação de anúncios com categoria, descrição, preço, unidade, quantidade, foto e localização.
- Busca por texto e filtros por categoria, unidade, estado e faixa de preço.
- Ordenação por data, preço e proximidade, quando o usuário tem coordenadas salvas.
- Negociações privadas com mensagens e opção de continuar a conversa pelo WhatsApp.
- Gerenciamento do estado do anúncio: ativo, pausado ou vendido.
- Avaliações disponíveis para as partes quando o vendedor registra a venda para um comprador que iniciou uma negociação no AgroOferta.

O sistema facilita a descoberta e a negociação entre produtores. Pagamentos e entrega dos produtos são combinados diretamente entre comprador e vendedor.

## Requisitos

- PHP 8.2 ou superior, com as extensões exigidas pelo Laravel 12.
- Composer.
- Node.js e npm.

## Instalação local (Windows PowerShell)

Na pasta do projeto, execute:

```powershell
composer install
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File database/database.sqlite | Out-Null
}
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm install
npm run build
```

O arquivo `.env.example` configura SQLite por padrão. O comando `php artisan migrate --seed` cria as tabelas e carrega as categorias iniciais (Sementes, Fertilizantes, Defensivos, Rações, Maquinário, Ferramentas, Animais e Outros). O seeder pode ser executado novamente sem duplicar essas categorias.

Para usar outro banco de dados, configure as variáveis `DB_*` no `.env` antes de executar as migrações. Nunca compartilhe o `.env` ou a chave da aplicação.

## Executar em desenvolvimento

```powershell
composer run dev
```

Esse comando inicia o servidor Laravel, o Vite e os processos de fila e logs configurados pelo projeto. Acesse o endereço local mostrado pelo servidor (normalmente `http://127.0.0.1:8000`).

## Fluxo principal

1. Crie uma conta e, se quiser usar ordenação por proximidade ou contato via WhatsApp, informe sua localização e telefone em **Meu contato**.
2. Publique um anúncio em **Anunciar**.
3. Encontre anúncios na página inicial usando busca, filtros e ordenação.
4. Abra um anúncio e inicie uma negociação para trocar mensagens com o vendedor.
5. Depois de concluir a venda, o vendedor pode marcar o anúncio como vendido e selecionar o comprador da negociação para habilitar as avaliações.

## Testes

Para executar a suíte automatizada:

```powershell
php artisan test
```

Os testes usam SQLite. A ordenação por distância depende de funções SQL que não estão disponíveis no SQLite e precisa ser validada em MySQL, MariaDB ou PostgreSQL.
