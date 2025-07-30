# 🛣️ Sistema de Cadastro de Trechos de Rodovias

Sistema web para cadastro e visualização de trechos de rodovias brasileiras, utilizando a API do DNIT para obtenção de dados geográficos.

## 🚀 Tecnologias Utilizadas

- **Backend:** Laravel 9.0 + PHP 8.1
- **Frontend:** Vue.js 3 + Inertia.js
- **Banco de Dados:** MySQL
- **Mapas:** Leaflet.js
- **Styling:** Tailwind CSS
- **Ambiente:** XAMPP + VSCode

## 📋 Funcionalidades

- ✅ Cadastro de trechos de rodovias
- ✅ Integração com API do DNIT para obtenção de geometria
- ✅ Visualização de trechos no mapa interativo
- ✅ Listagem e gerenciamento de trechos cadastrados
- ✅ Autenticação de usuários
- ✅ Interface responsiva

## 🔧 Pré-requisitos

- **XAMPP** (PHP 8.1+, MySQL, Apache)
- **Node.js** 16+ e **npm**
- **Composer**
- **VSCode** (recomendado)

## 📦 Instalação

### 1. Clone o Projeto
```bash
git clone <url-do-repositorio>
cd desafio-trechos
```

### 2. Configure o XAMPP
1. Inicie o **Apache** e **MySQL** no painel do XAMPP
2. Acesse `http://localhost/phpmyadmin`
3. Crie um banco de dados chamado `desafio_trechos`

### 3. Configuração do Laravel

#### 3.1 Instale as dependências PHP
```bash
composer install
```

#### 3.2 Configure o arquivo `.env`
```bash
cp .env.example .env
```

Edite o arquivo `.env` com as configurações do banco:
```env
APP_NAME="Sistema de Trechos"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=desafio_trechos
DB_USERNAME=root
DB_PASSWORD=

# Configurações adicionais...
```

#### 3.3 Gere a chave da aplicação
```bash
php artisan key:generate
```

#### 3.4 Execute as migrações e seeders
```bash
php artisan migrate --seed
```

### 4. Configuração do Frontend

#### 4.1 Instale as dependências Node.js
```bash
npm install
```

#### 4.2 Compile os assets
```bash
# Para desenvolvimento
npm run dev

# Para produção
npm run build
```

### 5. Inicie o Servidor

#### 5.1 Servidor Laravel
```bash
php artisan serve
```

#### 5.2 (Opcional) Watcher para desenvolvimento
Em um terminal separado:
```bash
npm run dev
```

## 🌐 Acesso à Aplicação

- **URL:** http://127.0.0.1:8000
- **Login:** Crie uma conta através do registro
- **Trechos:** http://127.0.0.1:8000/trechos

## 📁 Estrutura do Projeto

```
desafio-trechos/
├── app/
│   ├── Http/Controllers/
│   │   └── TrechoController.php     # Controller principal
│   └── Models/
│       ├── Trecho.php               # Modelo do trecho
│       ├── Uf.php                   # Modelo das UFs
│       └── Rodovia.php              # Modelo das rodovias
├── database/
│   ├── migrations/                  # Migrações do banco
│   └── seeders/                     # Seeds com dados iniciais
├── resources/
│   └── js/
│       └── Pages/
│           └── Trechos/
│               └── Index.vue        # Página principal
└── routes/
    └── web.php                      # Rotas da aplicação
```

## 🗄️ Banco de Dados

### Tabelas Principais

#### `ufs`
- **id:** Chave primária
- **sigla:** Sigla da UF (2 caracteres)
- **nome:** Nome completo da UF

#### `rodovias`
- **id:** Chave primária  
- **nome:** Código da rodovia (ex: "060")

#### `trechos`
- **id:** Chave primária
- **data_referencia:** Data de referência do trecho
- **uf_id:** FK para UFs
- **rodovia_id:** FK para rodovias
- **tipo_trecho:** Tipo do trecho (B, etc.)
- **quilometragem_inicial:** Km inicial
- **quilometragem_final:** Km final
- **geo:** Dados geográficos (JSON)

## 🔌 Integração com API DNIT

O sistema integra com a API oficial do DNIT para obter dados geográficos:

- **Endpoint:** `https://servicos.dnit.gov.br/sgplan/apigeo/rotas/espacializarlinha`
- **Método:** GET
- **Parâmetros:**
  - `br`: Código da rodovia (3 dígitos)
  - `tipo`: Tipo do trecho
  - `uf`: Sigla da UF
  - `cd_tipo`: Código do tipo (string "null" para tipo B)
  - `data`: Data de referência (yyyy-mm-dd)
  - `kmi`: Quilometragem inicial (formato 0.000)
  - `kmf`: Quilometragem final (formato 0.000)

## 🎯 Como Usar

### 1. Cadastrar um Trecho
1. Acesse a página de trechos
2. Preencha o formulário:
   - **Data de Referência:** Data do trecho
   - **UF:** Selecione a unidade federativa
   - **Rodovia:** Escolha a rodovia (ex: 060)
   - **Km Inicial/Final:** Quilometragem do trecho
   - **Tipo:** Tipo do trecho (B - Básico)
3. Clique em "Adicionar"
4. O sistema buscará a geometria na API do DNIT

### 2. Visualizar no Mapa
1. Na lista de trechos cadastrados
2. Clique em "Visualizar" no trecho desejado
3. A geometria será exibida no mapa interativo

### 3. Excluir Trecho
1. Na lista de trechos
2. Clique em "Deletar"
3. Confirme a exclusão

## 🛠️ Desenvolvimento

### Comandos Úteis

```bash
# Limpar cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Recompilar assets
npm run build

# Verificar logs
tail -f storage/logs/laravel.log

# Executar testes
php artisan test
```

### Estrutura de Rotas

```php
// Rota principal (redireciona para trechos)
Route::get('/', function () {
    return redirect()->route('trechos.index');
});

// Rotas de trechos (protegidas por autenticação)
Route::resource('trechos', TrechoController::class)
    ->only(['index', 'store', 'destroy'])
    ->middleware(['auth', 'verified']);
```

## 🐛 Solução de Problemas

### Problema: API DNIT retorna HTML
**Solução:** O sistema tenta múltiplos endpoints automaticamente

### Problema: Geometria não aparece no mapa
**Solução:** Verifique o console do navegador para logs de debug

### Problema: Erro de migração
**Solução:** 
```bash
php artisan migrate:fresh --seed
```

### Problema: Assets não carregam
**Solução:**
```bash
npm run build
php artisan cache:clear
```

## 📝 Logs

Os logs da aplicação ficam em:
- **Laravel:** `storage/logs/laravel.log`
- **Navegador:** Console do DevTools (F12)


---

**Desenvolvido usando Laravel + Vue.js + Inertia.js**
