# Documentação do Sistema — Moura Galvão Advogados

Sistema web de gestão de processos jurídicos (Direito Trabalhista), desenvolvido em **PHP + MySQL + JavaScript**, recriado a partir do Microsoft Access original.

---

## Índice

1. [Visão geral](#visão-geral)
2. [Banco de dados](#banco-de-dados)
3. [Estrutura do projeto (MVC)](#estrutura-do-projeto-mvc)
4. [Como funciona cada parte](#como-funciona-cada-parte)
5. [Telas e funcionalidades](#telas-e-funcionalidades)
6. [API (comunicação JavaScript ↔ PHP)](#api-comunicação-javascript--php)
7. [Como instalar e executar](#como-instalar-e-executar)
8. [Transferir para outro computador](#transferir-para-outro-computador)
9. [Acesso na rede local](#acesso-na-rede-local)
10. [Colocar online (hospedagem)](#colocar-online-hospedagem)
11. [Scripts auxiliares](#scripts-auxiliares)
12. [Histórico dos dados](#histórico-dos-dados)
13. [Solução de problemas](#solução-de-problemas)

---

## Visão geral

| Item | Detalhe |
|------|---------|
| **Cliente** | Moura Galvão Advogados Associados |
| **Finalidade** | Consultar, cadastrar e gerar relatórios de processos trabalhistas |
| **Linguagens** | PHP 8, HTML, CSS, JavaScript |
| **Banco de dados** | **MySQL** (MariaDB no XAMPP) |
| **Arquitetura** | MVC (Model — View — Controller) |
| **Servidor local** | PHP embutido na porta **8080** (via `iniciar.bat`) |
| **Registros** | ~16.876 processos |

---

## Banco de dados

### Qual banco está em uso?

O sistema utiliza **MySQL**, não SQLite.

| Configuração | Valor |
|--------------|-------|
| **Servidor** | `localhost` |
| **Porta** | `3306` |
| **Nome do banco** | `advocacia` |
| **Tabela** | `planilha1` |
| **Usuário** | `root` (padrão XAMPP) |
| **Senha** | *(vazia no XAMPP local)* |
| **Charset** | `utf8mb4` |
| **Fuso horário** | `America/Bahia` (Salvador-BA) |

As credenciais ficam em:

```
advocacia/config/config.local.php
```

Exemplo:

```php
return [
    'host'     => 'localhost',
    'port'     => 3306,
    'database' => 'advocacia',
    'username' => 'root',
    'password' => '',
    'charset'  => 'utf8mb4',
    'timezone' => 'America/Bahia',
];
```

### Estrutura da tabela `planilha1`

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `CADASTRO` | INT | Chave primária (ID do registro) |
| `RECLAMANTE` | VARCHAR | Nome do reclamante |
| `DATA_NASC` | VARCHAR | Data de nascimento |
| `ENDERE_O` | VARCHAR | Endereço do reclamante |
| `FONE_RTE` | VARCHAR | Telefone 1 |
| `FONE_RTE_2_` | VARCHAR | Telefone 2 |
| `FONE_RTE_3_` | VARCHAR | Telefone 3 |
| `FONE_RTE_4_` | VARCHAR | Telefone 4 |
| `FALAR_COM_FONE_1_` | VARCHAR | Contato do telefone 1 |
| `FALAR_COM_FONE_2_` | VARCHAR | Contato do telefone 2 |
| `FALAR_COM_FONE_3_` | VARCHAR | Contato do telefone 3 |
| `FALAR_COM_FONE_4_` | VARCHAR | Contato do telefone 4 |
| `RECLAMADA` | VARCHAR | Nome da reclamada |
| `END_RDA` | VARCHAR | Endereço da reclamada |
| `COL_2__RECLAMADA` | VARCHAR | 2ª reclamada |
| `END_RDA_1` | VARCHAR | Endereço da 2ª reclamada |
| `JUNTA` | VARCHAR | Junta / câmara |
| `PROC` | VARCHAR | Número do processo |
| `DIA_AUD` | VARCHAR | Data da audiência |
| `HORA_AUD` | VARCHAR | Hora da audiência |
| `PRA_A_DIA` | VARCHAR | Data do prazo |
| `PRA_A_HORA` | VARCHAR | Hora do prazo |
| `ANDAMENTO` | TEXT | Histórico e status do processo |
| `CTPS` | VARCHAR | CTPS |
| `IDENTIDADE` | VARCHAR | Identidade |
| `CPF` | VARCHAR | CPF |
| `cxpra_a` | VARCHAR | Campo auxiliar |

Script de criação: `sql/schema_mysql.sql`

### O arquivo `sistema.db` (SQLite)

Na pasta pai (`Servio/sistema.db`) ainda existe o banco SQLite original, gerado a partir do Excel. **O sistema PHP não usa mais esse arquivo.** Ele foi mantido como backup e como fonte para a migração inicial.

---

## Estrutura do projeto (MVC)

```
Servio/
├── sistema.db                 ← Backup SQLite (não usado pelo PHP)
├── Pasta1.xlsx                ← Planilha Excel original
├── banco.py                   ← Script que importou Excel → SQLite
│
└── advocacia/                 ← Aplicação web (esta pasta)
    ├── index.php              ← Menu principal + aniversariantes
    ├── cadastro.php           ← Cadastro de clientes
    ├── consulta.php           ← Consultas (processo/reclamante/reclamada)
    ├── relatorio.php          ← Pautas com filtro por data
    ├── iniciar.bat            ← Inicia o servidor local
    ├── migrar.bat             ← Migra SQLite → MySQL
    │
    ├── api/
    │   └── index.php          ← Entrada da API AJAX
    │
    ├── config/
    │   ├── database.php       ← Conexão MySQL + fuso horário
    │   ├── config.local.php   ← Credenciais (não compartilhar)
    │   └── config.local.php.example
    │
    ├── controllers/
    │   └── ApiController.php  ← Respostas JSON da API
    │
    ├── models/
    │   └── ProcessoModel.php  ← Consultas SQL e regras de negócio
    │
    ├── views/
    │   ├── layout.php         ← Layout base das páginas internas
    │   └── partials/
    │       ├── header.php     ← Cabeçalho com logo
    │       ├── footer.php     ← Rodapé
    │       └── form_fields.php← Campos dos formulários
    │
    ├── assets/
    │   ├── css/style.css      ← Estilos visuais
    │   ├── js/app.js          ← Autocomplete nos formulários
    │   ├── js/dashboard.js    ← Aniversariantes + WhatsApp
    │   └── img/               ← Logo e imagens
    │
    ├── scripts/
    │   └── migrar_sqlite_para_mysql.php
    │
    └── sql/
        ├── schema_mysql.sql   ← Criação da tabela no MySQL
        └── schema.sql         ← Estrutura antiga (SQLite)
```

### O que é MVC?

| Camada | Pasta | Responsabilidade |
|--------|-------|------------------|
| **Model** | `models/` | Acessa o banco, executa SQL, formata dados |
| **View** | `views/`, `*.php` | Exibe HTML para o usuário |
| **Controller** | `controllers/` | Recebe requisições da API e chama o Model |

---

## Como funciona cada parte

### 1. Conexão com o banco — `config/database.php`

- Abre conexão **PDO** com MySQL
- Define fuso horário **`America/Bahia`** (evita aniversariantes do dia errado)
- Função `getConnection()` — usada em todo o sistema
- Função `sqlId()` — protege nomes de colunas/tabelas

### 2. Regras de negócio — `models/ProcessoModel.php`

Centraliza todas as operações no banco:

| Método | Função |
|--------|--------|
| `autocomplete()` | Busca por nome, reclamada ou processo (autocomplete) |
| `buscarPorId()` | Retorna um registro completo pelo ID |
| `salvar()` | Insere ou atualiza cadastro |
| `proximoId()` | Próximo número de cadastro disponível |
| `contarProcessos()` | Total de registros (menu principal) |
| `pautaAudiencias()` | Relatório de audiências com filtro de data |
| `pautaReclamante()` | Relatório por reclamante com filtro de data |
| `aniversariantesDoDia()` | Quem faz aniversário hoje |

### 3. API — `api/index.php` + `controllers/ApiController.php`

O JavaScript chama a API sem recarregar a página. A API devolve JSON.

### 4. Autocomplete — `assets/js/app.js`

Ao digitar no campo de busca:

1. JavaScript envia o texto para a API (`?acao=buscar`)
2. API consulta o MySQL
3. Retorna lista de sugestões
4. Ao selecionar, carrega o registro completo e preenche o formulário

Funciona em: **Cadastro**, **Consulta por processo**, **Consulta por reclamante**, **Consulta por reclamada**.

### 5. Aniversariantes — `assets/js/dashboard.js`

- Busca no MySQL quem faz aniversário **no dia atual** (mês/dia da `DATA_NASC`)
- Lista com checkbox para seleção múltipla
- Nomes **sem telefone** aparecem com fundo **amarelo claro**
- Permite importar imagem e enviar mensagem via **WhatsApp Web**
- Use `{nome}` na mensagem para personalizar automaticamente

---

## Telas e funcionalidades

| Tela | Arquivo | Descrição |
|------|---------|-----------|
| Menu principal | `index.php` | Painel com acesso a todas as funções |
| Cadastro | `cadastro.php` | Incluir/editar clientes e processos |
| Consulta por processo | `consulta.php?tipo=processo` | Busca somente leitura |
| Consulta por reclamante | `consulta.php?tipo=reclamante` | Busca somente leitura |
| Consulta por reclamada | `consulta.php?tipo=reclamada` | Busca somente leitura |
| Pauta de audiências | `relatorio.php?tipo=audiencias` | Relatório com filtro de data |
| Pauta reclamante | `relatorio.php?tipo=reclamante` | Relatório com filtro de data |

### Relatórios — filtro por período

Em **Pauta de Audiências** e **Pauta Reclamante**:

- **Data inicial** e **Data final**
- Filtra audiências entre as datas informadas
- Botão **Pesquisar** e **Limpar**
- Botão **Imprimir** para impressão pelo navegador

---

## API (comunicação JavaScript ↔ PHP)

Base: `http://localhost:8080/api/index.php`

| Ação | URL | Descrição |
|------|-----|-----------|
| Buscar | `?acao=buscar&q=termo&tipo=geral` | Autocomplete |
| Registro | `?acao=registro&id=26` | Dados completos de um cadastro |
| Salvar | `?acao=salvar` (POST JSON) | Gravar/atualizar cadastro |
| Próximo ID | `?acao=proximo_id` | Próximo número de cadastro |
| Aniversariantes | `?acao=aniversariantes` | Lista de aniversariantes do dia |

---

## Como instalar e executar

### Pré-requisitos

- **XAMPP** instalado (PHP + MySQL)
- Banco `advocacia` criado e populado (já feito pela migração)

### Passo a passo (Windows)

1. Abra o **Painel de Controle do XAMPP**
2. Clique em **Start** no **MySQL** (obrigatório)
3. Dê duplo clique em:

   ```
   advocacia\iniciar.bat
   ```

4. Abra no navegador:

   ```
   http://localhost:8080
   ```

5. **Não feche** a janela preta do terminal — ela é o servidor

### Linux

```bash
sudo apt install php php-mysql
cd advocacia
php -S 0.0.0.0:8080
```

---

## Acesso na rede local (escritório / Wi-Fi)

Para **todos os computadores da mesma rede** acessarem o sistema **sem internet**:

### No computador servidor (onde ficam os arquivos)

1. Ligue o **MySQL** no XAMPP
2. Execute **`iniciar.bat`**
3. Anote o IP que aparece na tela, ex.: `http://192.168.1.105:8080`

### Nos outros computadores (mesma rede Wi-Fi)

Abra o navegador e acesse:

```
http://IP_DO_SERVIDOR:8080
```

Exemplo: `http://192.168.1.105:8080`

### Requisitos

| Item | Detalhe |
|------|---------|
| Mesma rede | Todos no **mesmo Wi-Fi** ou cabo da mesma rede |
| PC servidor ligado | O PC com o `iniciar.bat` rodando **não pode desligar** |
| Janela aberta | **Não feche** a janela preta do `iniciar.bat` |
| MySQL ligado | MySQL do XAMPP deve estar **Running** |
| Firewall | Liberar porta **8080** na primeira vez (ver abaixo) |

### Liberar no Firewall do Windows (primeira vez)

Se os outros PCs não conseguirem acessar:

1. Painel de Controle → **Firewall do Windows** → **Configurações avançadas**
2. **Regras de Entrada** → **Nova Regra**
3. Tipo: **Porta** → TCP → **8080**
4. **Permitir conexão** → marque **Rede privada**
5. Nome: `Advocacia Rede Local`

Ou, quando o Windows perguntar ao executar o `iniciar.bat`, clique em **Permitir** na rede privada.

### Descobrir o IP manualmente

No PC servidor, abra o Prompt e digite:

```
ipconfig
```

Procure **Endereço IPv4**, ex.: `192.168.1.105`

### Copiar para outro PC na rede

**Não precisa** copiar o projeto em cada PC. Só **um computador** roda o servidor; os demais acessam pelo navegador usando o IP.

Se quiser um **segundo servidor de backup** em outro PC, copie a pasta `advocacia` + importe o banco MySQL (veja seção acima).

---

## Colocar online (hospedagem)

Para uso na internet (escritório remoto, equipe externa):

1. Contratar hospedagem com **PHP + MySQL**
2. Enviar a pasta `advocacia` via FTP
3. Criar banco MySQL no painel da hospedagem
4. Atualizar `config/config.local.php` com host, usuário e senha da hospedagem
5. Importar dados com `migrar.bat` ou phpMyAdmin
6. **Recomendado:** adicionar login/senha (hoje o sistema não tem autenticação)
7. Usar **HTTPS** (cadeado no navegador)

---

## Scripts auxiliares

| Arquivo | Função |
|---------|--------|
| `iniciar.bat` | Inicia servidor PHP na porta 8080 |
| `preparar_transferencia.bat` | **PC antigo** — exporta banco e prepara pacote |
| `exportar_banco.bat` | Exporta MySQL para `sql/backup_advocacia.sql` |
| `instalar_novo_pc.bat` | **PC novo** — importa banco e verifica instalação |
| `migrar.bat` | Recria tabela MySQL e importa dados do SQLite |
| `scripts/migrar_sqlite_para_mysql.php` | Script PHP da migração |
| `scripts/verificar_instalacao.php` | Testa conexão e total de registros |
| `banco.py` (pasta Servio) | Importa Excel → SQLite |
| `sql/schema_mysql.sql` | SQL para criar banco e tabela no MySQL |
| `sql/backup_advocacia.sql` | Backup completo do banco (gerado na exportação) |

### Migrar dados novamente (SQLite → MySQL)

1. Ligue o MySQL no XAMPP
2. Execute `migrar.bat`
3. Aguarde a mensagem: *"Todos os dados foram transferidos!"*

---

## Histórico dos dados

```
Pasta1.xlsx  →  banco.py  →  sistema.db (SQLite)  →  migrar.bat  →  MySQL (advocacia)
   Excel          Python         Backup local            Migração         Banco em uso
```

---

## Solução de problemas

| Problema | Causa | Solução |
|----------|-------|---------|
| `ERR_CONNECTION_REFUSED` | Servidor PHP não está rodando | Execute `iniciar.bat` |
| Busca não carrega dados | MySQL desligado | Ligue MySQL no XAMPP |
| Aniversariantes do dia errado | Fuso horário | Já configurado `America/Bahia` em `config.local.php` |
| Lista vazia na busca | Erro SQL ou banco vazio | Verifique MySQL e tabela `planilha1` |
| Porta 8080 ocupada | Outro `iniciar.bat` aberto | Feche janelas duplicadas do terminal |

### Verificar se MySQL está ok

No XAMPP, MySQL deve estar com status **Running** (verde).

### Verificar total de registros

Acesse **http://localhost:8080** — o menu mostra o total exato de processos cadastrados.

---

## Transferir para outro computador

Guia completo: **[TRANSFERIR_PARA_OUTRO_PC.md](TRANSFERIR_PARA_OUTRO_PC.md)**

### Resumo automático (recomendado)

| Etapa | PC atual | PC novo |
|-------|----------|---------|
| 1 | Ligar MySQL no XAMPP | Instalar XAMPP |
| 2 | Executar `preparar_transferencia.bat` | Colar pasta `advocacia` |
| 3 | Copiar pasta `advocacia` inteira | Ligar MySQL no XAMPP |
| 4 | — | Executar `instalar_novo_pc.bat` |
| 5 | — | Executar `iniciar.bat` |

O script `preparar_transferencia.bat` exporta **todo o banco MySQL** para `sql/backup_advocacia.sql` (~16.876 registros). Leve esse arquivo junto com a pasta — **não precisa** copiar `sistema.db` nem `Pasta1.xlsx`.

No PC novo, `instalar_novo_pc.bat` importa o backup e verifica se tudo está OK.

### O que copiar

```
advocacia/                    ← pasta inteira
├── sql/backup_advocacia.sql  ← banco de dados (gerado pelo preparador)
├── config/config.local.php   ← configuração MySQL
├── instalar_novo_pc.bat      ← rodar no PC novo
└── iniciar.bat               ← iniciar o sistema
```

### Checklist rápido

| Item | Necessário? |
|------|-------------|
| XAMPP (PHP + MySQL) | Sim |
| Pasta `advocacia` completa | Sim |
| `sql/backup_advocacia.sql` | Sim (exportado no PC antigo) |
| `config.local.php` | Sim (já vem na pasta) |
| `sistema.db` | Não (só fallback se não houver backup SQL) |
| Internet | Não (uso local) |

---

## Copiar o projeto para outro computador (manual)

Se preferir fazer manualmente via phpMyAdmin, use os passos abaixo. Para o fluxo automático, veja a seção anterior.

### O que copiar

Copie **toda a pasta `advocacia`** para o outro PC.

**Forma mais fácil de levar o banco:** execute `preparar_transferencia.bat` ou `exportar_banco.bat` no PC atual.

### Passo a passo no computador novo

1. **Instalar XAMPP** — https://www.apachefriends.org/

2. **Copiar a pasta `advocacia`** — ex.: `C:\Servio\advocacia`

3. **Importar o banco MySQL** (escolha uma opção):

   **Opção A — Script automático (recomendado)**  
   Execute `instalar_novo_pc.bat` (usa `sql/backup_advocacia.sql`).

   **Opção B — phpMyAdmin**  
   No PC antigo: banco `advocacia` → Exportar → SQL.  
   No PC novo: phpMyAdmin → Importar o arquivo `.sql`.

   **Opção C — Migrar de novo**  
   Copie também `sistema.db` na pasta pai, ligue o MySQL e execute `migrar.bat`.

4. **Conferir `config/config.local.php`**

   ```php
   'host'     => 'localhost',
   'database' => 'advocacia',
   'username' => 'root',
   'password' => '',
   'timezone' => 'America/Bahia',
   ```

5. **Executar `iniciar.bat`** e abrir `http://localhost:8080`

### Checklist rápido (manual)

| Item | Necessário? |
|------|-------------|
| XAMPP (PHP + MySQL) | Sim |
| Pasta `advocacia` | Sim |
| Banco `advocacia` importado | Sim |
| `config.local.php` | Sim |
| `sistema.db` | Só se usar `migrar.bat` |
| Internet | Não (uso local) |

---

## Usar domínio na web (ex.: www.gestaoprocessosmolra.com.br)

**Não basta alterar um arquivo no projeto.** O domínio precisa de:

1. **Domínio registrado** (Registro.br, Hostinger, etc.)
2. **Servidor acessível pela internet** (hospedagem ou PC com IP fixo)
3. **DNS apontando** o domínio para o IP do servidor
4. **Servidor web** (Apache/Nginx — não só `iniciar.bat`)

> **Atenção:** Confira a grafia do domínio ao registrar (ex.: `gestaoprocessosmoura` vs `gestaoprocessosmolra`).

### Diferença importante

| Modo | URL | Quem acessa |
|------|-----|-------------|
| **Local (atual)** | `http://localhost:8080` | Só neste PC |
| **Rede do escritório** | `http://192.168.x.x:8080` | PCs na mesma Wi-Fi |
| **Domínio na internet** | `https://www.gestaoprocessosmolra.com.br` | Qualquer lugar |

O `iniciar.bat` serve para **testes locais**. Para domínio público use **hospedagem** ou **Apache do XAMPP** com DNS.

### Opção 1 — Hospedagem (recomendado)

1. Contratar plano **PHP + MySQL** (Hostinger, Locaweb, UOL Host, etc.)
2. Registrar o domínio `gestaoprocessosmolra.com.br`
3. Enviar a pasta `advocacia` via **FTP** para `public_html` (ou subpasta)
4. Criar banco MySQL no painel e importar os dados
5. Atualizar `config/config.local.php`:

   ```php
   'host'     => 'mysql.seudominio.com.br',  // host da hospedagem
   'database' => 'nome_do_banco',
   'username' => 'usuario_mysql',
   'password' => 'senha_mysql',
   ```

6. No painel da hospedagem, apontar o domínio para a pasta do sistema
7. Ativar **SSL/HTTPS** (Let's Encrypt — gratuito)

### Opção 2 — Servidor no escritório (PC ligado 24h)

1. IP **fixo** ou **DDNS** no roteador
2. **Port forwarding** porta 80/443 → PC do servidor
3. XAMPP → ligar **Apache** + **MySQL**
4. Copiar `advocacia` para `C:\xampp\htdocs\`
5. No **Registro.br**, criar registro **DNS tipo A**:

   ```
   www  →  A  →  SEU_IP_PUBLICO
   @    →  A  →  SEU_IP_PUBLICO
   ```

6. Configurar **Virtual Host** no Apache para o domínio
7. Menos estável e menos seguro que hospedagem profissional

### DNS (como o domínio encontra seu sistema)

```
Usuário digita: www.gestaoprocessosmolra.com.br
        ↓
   DNS (Registro.br)
        ↓
   IP do servidor (ex.: 200.100.50.25)
        ↓
   Apache/Nginx → PHP → MySQL
        ↓
   Sistema carrega normalmente
```

Propagação do DNS: **algumas horas até 48h**.

### Segurança antes de colocar online

| Item | Status atual | Recomendação |
|------|--------------|--------------|
| Login/senha | Não tem | **Adicionar** antes de publicar |
| HTTPS | Não | **Obrigatório** na internet |
| Backup MySQL | Manual | Backup automático diário |
| Firewall | — | Liberar só portas 80 e 443 |

O código usa caminhos relativos (`assets/css/...`), então **funciona em qualquer domínio** sem alterar URLs no PHP — basta hospedar corretamente.

---

## Contato do escritório

**Moura Galvão Advogados Associados**  
Rua Miguel Calmon, nº 506 — Salvador-BA  
Telefone: (71) 3327-2299  
Site: https://www.mouragalvao.com/

---

*Documentação gerada para o sistema de gestão de processos — Direito Trabalhista.*
