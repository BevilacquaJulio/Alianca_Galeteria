# Aliança Galeteria

Sistema esqueleto prototipo para uma galeteria artesanal, com loja pública, painel administrativo, controle de estoque, sorteios automáticos e geração de ofertas personalizadas com QR Code.

O projeto foi construído do zero em PHP puro e JavaScript vanilla, sem frameworks, com foco em uma arquitetura limpa, segura e com interface premium no tema escuro.

---

## Funcionalidades

### Loja Pública
- Página de vendas com tema escuro premium
- Hero animado e grid de produtos filtráveis por categoria
- Carrinho lateral (drawer) com persistência em `localStorage`
- Checkout integrado com criação automática de pedido
- Redirecionamento para WhatsApp com resumo do pedido
- Responsivo (desktop, tablet e mobile)

### Painel Administrativo
SPA navegada por hash, autenticada por sessão.

- **Dashboard** — KPIs de receita, pedidos, ticket médio e estoque baixo, com gráficos renderizados em Canvas puro (sem bibliotecas)
- **Pedidos** — listagem paginada, filtros por status e busca, criação manual, atualização de status e impressão de comprovante
- **Produtos** — CRUD completo com categorias, destaque e upload de imagem por URL
- **Estoque** — entradas, saídas, ajustes absolutos e histórico de movimentações por produto
- **Clientes** — cadastro, histórico de pedidos e total gasto
- **Upsell / QR Code** — geração de tokens únicos com SHA-256, QR Code real e envio direto por WhatsApp
- **Sorteios** — semanal e mensal, com RNG criptográfico (`random_int`) e simulação de data para teste
- **Relatórios** — resumo financeiro do período, ranking de produtos e clientes, gráficos de evolução e exportação em CSV/JSON

### Regras de Negócio
- Baixa de estoque em transação atômica ao confirmar pedido
- Reversão automática do estoque ao cancelar pedido
- Validação de estoque antes de gravar itens
- Tokens de upsell com hash no banco (o valor bruto nunca é armazenado)
- Sorteios consideram apenas pedidos com status `entregue` no período

---

## Stack

| Camada | Tecnologia |
|--------|-----------|
| Backend | PHP 8 (sem frameworks) |
| Banco de Dados | MySQL 8 / MariaDB |
| Acesso a dados | PDO com prepared statements |
| Frontend | HTML5, CSS3 e JavaScript puro |
| Gráficos | Canvas API (implementação própria) |
| QR Code | API pública (sem dependências no servidor) |
| Tipografia | Inter (Google Fonts) |

---

## Arquitetura

Separação em camadas seguindo o padrão MVC simplificado:

```
alianca_galeteria/
├── config.php             Configuração global
├── api.php                Router central (match expression por rota)
├── index.php              Loja pública
├── admin.php              Painel administrativo (SPA)
├── upsell.php             Página de oferta personalizada
│
├── app/
│   ├── Database.php       Conexão PDO singleton
│   ├── Auth.php           Sessão, CSRF e rate limit
│   ├── Helpers.php        Funções utilitárias globais
│   ├── Models/            Regra de negócio e acesso a dados
│   │   ├── Product.php
│   │   ├── Order.php
│   │   ├── Customer.php
│   │   ├── UpsellToken.php
│   │   └── Raffle.php
│   └── Controllers/       Resposta HTTP e validação
│       ├── AuthController.php
│       ├── ProductController.php
│       ├── OrderController.php
│       ├── CustomerController.php
│       ├── StockController.php
│       ├── UpsellController.php
│       ├── RaffleController.php
│       └── ReportController.php
│
├── assets/
│   ├── css/               Estilos (desktop-first)
│   ├── js/                Lógica de cada interface
│   └── img/               Imagens e logos
│
└── database/
    ├── schema.sql         Estrutura completa
    └── seed.sql           Dados iniciais
```

---

## Segurança

- **PDO com prepared statements** em 100% das queries
- **Senhas** com `password_hash` (bcrypt cost 12)
- **Rate limit** de login: 5 tentativas por 15 minutos por IP + e-mail
- **Sessão** com `HttpOnly`, `SameSite=Lax` e regeneração no login
- **Tokens de upsell** salvos apenas como hash SHA-256 com segredo da aplicação
- **Sanitização** de entrada (strip_tags + htmlspecialchars)
- **Headers** `X-Content-Type-Options`, `X-Frame-Options` e `Referrer-Policy`
- **Proteção de diretórios sensíveis** via `.htaccess` (`app/`, `database/`, `storage/`)

---

## API

Todas as rotas passam pelo roteador central `api.php?route=...` que usa `match` do PHP 8.

Alguns exemplos:

| Método | Rota | Descrição |
|--------|------|-----------|
| POST | `auth/login` | Autentica admin |
| GET | `dashboard` | Retorna KPIs e dados do painel inicial |
| GET | `products/public` | Lista de produtos para a loja |
| POST | `orders` | Cria pedido e baixa estoque |
| PUT | `orders/{id}/status` | Atualiza status (com reversão se cancelado) |
| POST | `upsell` | Gera token + QR Code + mensagem WhatsApp |
| GET | `upsell/verify/{token}` | Valida token e retorna dados da oferta |
| POST | `raffles/weekly` | Realiza sorteio da semana anterior |
| POST | `raffles/monthly` | Realiza sorteio do mês anterior |
| GET | `reports/sales` | Relatório consolidado do período |
| GET | `reports/export-csv` | Exporta CSV |

---

## Banco de Dados

O schema conta com **14 tabelas** relacionadas por chaves estrangeiras:

`admins`, `login_attempts`, `categories`, `products`, `stock`, `stock_movements`, `customers`, `orders`, `order_items`, `upsell_tokens`, `raffles`, `raffle_participants`.

Relacionamentos principais:
- `products` ↔ `categories` (N:1)
- `products` ↔ `stock` (1:1)
- `orders` ↔ `customers` (N:1)
- `orders` ↔ `order_items` ↔ `products` (N:N)
- `raffles` ↔ `raffle_participants` (1:N)

---

## Destaques Técnicos

- **Transações** garantem consistência de estoque (rollback em falha)
- **Match expression** do PHP 8 no roteador torna rotas declarativas e tipadas
- **Charts em Canvas** próprios (line, bar, donut) — zero dependências de front
- **SPA vanilla** usando `hashchange` e renderização por função
- **Desktop-first** com breakpoints em 1024px (tablet) e 640px (mobile)

---

## Paleta Visual

O sistema usa um tema escuro gastronômico inspirado em marcas premium:

| Cor | Hex | Uso |
|-----|-----|-----|
| Background | `#121212` | Fundo principal |
| Card | `#1B1B1B` | Painéis e cards |
| Borda | `#2A2A2A` | Divisores |
| Vinho | `#6A0F1F` | Cor primária (CTAs) |
| Dourado | `#D48A1C` | Destaques e preços |
| Brasa | `#E4571E` | Alertas e hover |

---

## Licença

Projeto pessoal. Código disponível para consulta e estudo.
