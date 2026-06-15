# Moura-Galvao

Sistema web de gestão de processos jurídicos — **Moura Galvão Advogados Associados** (Direito Trabalhista).

Desenvolvido em **PHP + MySQL + JavaScript**, com interface inspirada no Microsoft Access original. Inclui cadastro, consultas, relatórios, autocomplete e envio de mensagens de aniversário via WhatsApp.

**Repositório:** https://github.com/Ricardo-Santos-Tostas/Galvao

---

## Índice

1. [Requisitos](#requisitos)
2. [Estrutura do projeto](#estrutura-do-projeto)
3. [Transferir para outro PC — passo a passo completo](#transferir-para-outro-pc--passo-a-passo-completo)
4. [Uso diário](#uso-diário)
5. [Acesso na rede local (outros computadores)](#acesso-na-rede-local-outros-computadores)
6. [Atualizar dados no GitHub (PC antigo)](#atualizar-dados-no-github-pc-antigo)
7. [Solução de problemas](#solução-de-problemas)
8. [Documentação adicional](#documentação-adicional)

---

## Requisitos

| Item | Detalhe |
|------|---------|
| **Sistema operacional** | Windows 10 ou 11 |
| **XAMPP** | PHP 8 + MySQL/MariaDB — [download](https://www.apachefriends.org/) |
| **Navegador** | Chrome, Edge ou Firefox |
| **Internet** | Só na instalação (baixar XAMPP e clonar o GitHub). Depois funciona **sem internet** |
| **Espaço em disco** | ~500 MB (XAMPP + projeto + banco) |

O repositório já inclui:

- Código completo do sistema (`advocacia/`)
- Banco de dados MySQL exportado (`advocacia/sql/backup_advocacia.sql` — ~16.876 registros)
- Configuração pronta (`advocacia/config/config.local.php`)
- Backup SQLite (`sistema.db`) e planilha original (`Pasta1.xlsx`)

---

## Estrutura do projeto

```
Galvao/
├── README.md                          ← este arquivo
├── advocacia/                         ← sistema principal
│   ├── iniciar.bat                    ← inicia o servidor (uso diário)
│   ├── instalar_novo_pc.bat           ← instala no PC novo (rodar 1x)
│   ├── preparar_transferencia.bat     ← exporta banco no PC antigo
│   ├── exportar_banco.bat             ← só exporta o MySQL
│   ├── migrar.bat                     ← migra SQLite → MySQL (alternativa)
│   ├── index.php                      ← menu principal
│   ├── cadastro.php                   ← cadastro/edição
│   ├── consulta.php                   ← consultas
│   ├── relatorio.php                  ← relatórios
│   ├── config/
│   │   ├── config.local.php           ← credenciais MySQL
│   │   └── config.local.php.example   ← modelo de configuração
│   ├── sql/
│   │   ├── backup_advocacia.sql       ← banco completo (dados + estrutura)
│   │   └── schema_mysql.sql           ← só estrutura (sem dados)
│   ├── assets/                        ← CSS, JS, imagens
│   ├── models/                        ← regras de negócio
│   ├── controllers/                   ← API
│   └── views/                         ← telas HTML
├── sistema.db                         ← backup SQLite (opcional)
└── Pasta1.xlsx                        ← planilha Excel original
```

---

## Transferir para outro PC — passo a passo completo

### Forma recomendada: baixar do GitHub

Use esta opção se o projeto já está no repositório **Galvao** (como está agora).

---

### PASSO 1 — Instalar o XAMPP no PC novo

1. Acesse https://www.apachefriends.org/
2. Baixe o **XAMPP para Windows**
3. Execute o instalador
4. Na instalação, marque pelo menos:
   - **Apache** (opcional para este sistema)
   - **MySQL** (obrigatório)
   - **PHP** (obrigatório)
5. Pasta padrão de instalação: `C:\xampp`
6. Se o Windows Firewall perguntar, clique em **Permitir acesso**

> O sistema usa o PHP embutido (`iniciar.bat`). Não é obrigatório ligar o Apache no XAMPP.

---

### PASSO 2 — Baixar o projeto do GitHub

**Opção A — Git (recomendado)**

Se não tiver o Git, instale em: https://git-scm.com/download/win

Abra o **Prompt de Comando** ou **PowerShell** e execute:

```powershell
cd C:\
git clone https://github.com/Ricardo-Santos-Tostas/Galvao.git
```

O projeto ficará em:

```
C:\Galvao\
```

**Opção B — Download ZIP (sem Git)**

1. Acesse https://github.com/Ricardo-Santos-Tostas/Galvao
2. Clique no botão verde **Code**
3. Clique em **Download ZIP**
4. Extraia o ZIP em `C:\Galvao` (ou outro local de sua preferência)

---

### PASSO 3 — Ligar o MySQL

1. Abra o **XAMPP Control Panel** (ícone laranja)
2. Na linha **MySQL**, clique em **Start**
3. Aguarde ficar com fundo **verde** e a palavra **Running**

Se o MySQL não iniciar:
- Verifique se outro programa usa a porta 3306 (outro MySQL instalado)
- Feche programas que possam conflitar e tente novamente

---

### PASSO 4 — Instalar o banco de dados

1. Abra a pasta do sistema:

   ```
   C:\Galvao\advocacia
   ```

2. Dê **duplo clique** em:

   ```
   instalar_novo_pc.bat
   ```

3. Leia a mensagem na tela e pressione qualquer tecla para continuar

4. Aguarde a importação — pode levar **1 a 3 minutos**

5. Ao final, deve aparecer:

   ```
   INSTALACAO CONCLUIDA COM SUCESSO
   ```

O script faz automaticamente:

- Verifica se o XAMPP/PHP/MySQL estão instalados
- Cria `config.local.php` se não existir
- Importa `sql/backup_advocacia.sql` no MySQL
- Cria o banco `advocacia` com a tabela `planilha1`
- Verifica conexão e total de registros

> **Rodar apenas uma vez** no PC novo. Se precisar reinstalar o banco, execute novamente.

---

### PASSO 5 — Iniciar o sistema

1. Na mesma pasta `advocacia`, dê duplo clique em:

   ```
   iniciar.bat
   ```

2. Uma janela preta (terminal) vai abrir — **não feche essa janela**

3. Na tela aparecerá algo como:

   ```
   Neste computador:
     http://localhost:8080

   Outros PCs na mesma rede Wi-Fi:
     http://192.168.1.105:8080
   ```

4. Abra o navegador e acesse:

   ```
   http://localhost:8080
   ```

5. O menu principal deve abrir com o logo **Moura Galvão** e o total de processos cadastrados

---

### PASSO 6 — Conferir se está tudo certo

| Verificação | O que deve acontecer |
|-------------|----------------------|
| Menu principal | Mostra total de processos (ex.: **16.876**) |
| Consulta por nome | Autocomplete funciona ao digitar |
| Cadastro | Abre formulário com campos do processo |
| Relatórios | Pauta de audiências e reclamante abrem |
| Aniversariantes | Botão no menu lista aniversariantes do dia |

Se a lista estiver vazia, volte ao **Passo 4** e execute `instalar_novo_pc.bat` novamente com o MySQL ligado.

---

### Forma alternativa: pen drive ou pasta copiada

Se não quiser usar o GitHub:

**No PC antigo:**

1. Ligue o MySQL no XAMPP
2. Execute `advocacia\preparar_transferencia.bat`
3. Copie a pasta `advocacia` inteira para pen drive

**No PC novo:**

Siga os passos 1, 3, 4 e 5 acima (instale XAMPP, cole a pasta, rode `instalar_novo_pc.bat` e `iniciar.bat`).

Guia detalhado: [advocacia/TRANSFERIR_PARA_OUTRO_PC.md](advocacia/TRANSFERIR_PARA_OUTRO_PC.md)

---

## Uso diário

Toda vez que for usar o sistema:

1. Abra o **XAMPP** → **Start** no **MySQL**
2. Execute `advocacia\iniciar.bat`
3. Acesse **http://localhost:8080**
4. Para parar: feche a janela do `iniciar.bat` ou pressione **Ctrl+C**

---

## Acesso na rede local (outros computadores)

Para que **outros PCs do escritório** acessem o sistema na mesma rede Wi-Fi:

### No PC servidor (onde está o sistema)

1. MySQL ligado no XAMPP
2. `iniciar.bat` rodando
3. Anote o IP que aparece na tela (ex.: `192.168.1.105`)

### Nos outros PCs

Abra o navegador:

```
http://IP_DO_SERVIDOR:8080
```

Exemplo: `http://192.168.1.105:8080`

### Liberar no Firewall (primeira vez)

Se os outros PCs não conseguirem acessar:

1. **Painel de Controle** → **Firewall do Windows** → **Configurações avançadas**
2. **Regras de Entrada** → **Nova Regra**
3. Tipo: **Porta** → TCP → **8080**
4. **Permitir a conexão**
5. Nome: `Advocacia PHP`

### Requisitos da rede

| Item | Detalhe |
|------|---------|
| Mesma rede | Todos no mesmo Wi-Fi ou cabo |
| PC servidor ligado | O PC com `iniciar.bat` não pode desligar |
| Janela aberta | Não feche o terminal do `iniciar.bat` |
| MySQL ligado | MySQL do XAMPP em **Running** |

---

## Atualizar dados no GitHub (PC antigo)

Se fez alterações no banco e quer enviar para o GitHub:

1. Ligue o MySQL no XAMPP
2. Execute `advocacia\exportar_banco.bat` (atualiza `backup_advocacia.sql`)
3. No terminal, na pasta do projeto:

```powershell
cd C:\Galvao
git add .
git commit -m "Atualiza banco de dados"
git push
```

> **Atenção:** o repositório contém dados de clientes. Mantenha o repositório como **privado** no GitHub.

---

## Solução de problemas

| Problema | Causa provável | Solução |
|----------|----------------|---------|
| `ERR_CONNECTION_REFUSED` | Servidor não está rodando | Execute `iniciar.bat` |
| Tela em branco / erro PHP | XAMPP não instalado | Instale o XAMPP |
| MySQL não conecta | MySQL desligado | XAMPP → Start no MySQL |
| Lista vazia / sem dados | Banco não importado | Execute `instalar_novo_pc.bat` |
| `backup_advocacia.sql` não encontrado | Arquivo ausente | Baixe de novo do GitHub ou rode `exportar_banco.bat` no PC antigo |
| Autocomplete não funciona | MySQL off ou banco vazio | Verifique MySQL e `instalar_novo_pc.bat` |
| Aniversariantes do dia errado | Fuso horário | Já configurado `America/Bahia` em `config.local.php` |
| Porta 8080 ocupada | Outro `iniciar.bat` aberto | Feche janelas duplicadas do terminal |
| Outros PCs não acessam | Firewall bloqueando | Libere porta **8080** (ver seção acima) |
| XAMPP em `D:\xampp` | Instalação em outro disco | Os scripts detectam `C:\` e `D:\` automaticamente |

### Configuração do banco (`config/config.local.php`)

Padrão para XAMPP local:

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

Se alterou a senha do MySQL no XAMPP, atualize o campo `password`.

---

## Checklist — PC novo

- [ ] XAMPP instalado
- [ ] Projeto baixado do GitHub (ou copiado)
- [ ] MySQL ligado no XAMPP (verde / Running)
- [ ] `instalar_novo_pc.bat` executado com sucesso
- [ ] `iniciar.bat` rodando
- [ ] `http://localhost:8080` abre o menu
- [ ] Total de processos aparece no menu
- [ ] Consulta por nome funciona

---

## Documentação adicional

| Arquivo | Conteúdo |
|---------|----------|
| [advocacia/DOCUMENTACAO.md](advocacia/DOCUMENTACAO.md) | Documentação técnica completa (MVC, API, banco, telas) |
| [advocacia/TRANSFERIR_PARA_OUTRO_PC.md](advocacia/TRANSFERIR_PARA_OUTRO_PC.md) | Guia resumido de transferência |
| [advocacia/README.md](advocacia/README.md) | Início rápido na pasta do sistema |

---

## Contato

**Moura Galvão Advogados Associados**  
Rua Miguel Calmon, nº 506 — Salvador-BA  
Telefone: (71) 3327-2299  
Site: https://www.mouragalvao.com/

---

*Tecnologias: PHP 8 · MySQL/MariaDB · HTML · CSS · JavaScript · XAMPP*
