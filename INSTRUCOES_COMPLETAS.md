# Moura Galvão — Instruções completas

Sistema de Gestão de Processos · PHP + MySQL + XAMPP  
**Repositório:** https://github.com/Ricardo-Santos-Tostas/Galvao

Este arquivo explica:

1. [Instalação em um PC Windows novo](#1-instalação-em-um-pc-windows-novo)
2. [Atualizar do seu PC para o PC do cliente](#2-atualizar-do-seu-pc-para-o-pc-do-cliente)
3. [Backup do banco de dados](#3-backup-do-banco-de-dados)
4. [Uso diário](#4-uso-diário)
5. [Arquivos importantes](#5-arquivos-importantes)
6. [O que não fazer](#6-o-que-não-fazer)
7. [Solução de problemas](#7-solução-de-problemas)

---

## Requisitos

| Item | Detalhe |
|------|---------|
| Windows | 10 ou 11 |
| XAMPP | PHP 8 + MySQL — https://www.apachefriends.org/ |
| Git | https://git-scm.com/download/win |
| Internet | Necessária para baixar e atualizar pelo GitHub |
| Espaço | ~500 MB |

---

## 1. Instalação em um PC Windows novo

Existem **duas formas** de rodar o sistema. Use a que combinar com o PC.

### Forma A — PC de desenvolvimento (seu computador)

Pasta típica: `C:\Users\Ricardo\Desktop\Servio`

| Passo | O que fazer |
|-------|-------------|
| 1 | Instalar XAMPP em `C:\xampp` |
| 2 | Clonar o GitHub na pasta Servio (ou baixar ZIP) |
| 3 | Ligar **MySQL** no painel do XAMPP |
| 4 | Entrar em `Servio\advocacia` e executar **`instalar_novo_pc.bat`** (importa o banco) |
| 5 | Executar **`iniciar.bat`** |
| 6 | Abrir no navegador: **http://localhost:8080** |

> O `iniciar.bat` usa o PHP embutido na porta **8080**. Não precisa ligar o Apache.

---

### Forma B — PC do cliente (escritório)

Pasta do Git: `C:\Servio\Galvao`  
Sistema no XAMPP: `C:\xampp\htdocs\advocacia`  
Acesso: **http://localhost/advocacia**

#### Opção 1 — Instalação automática (recomendada)

1. Instale o **XAMPP** em `C:\xampp`
2. Instale o **Git**
3. Abra o **PowerShell como Administrador**
4. Execute:

```powershell
cd C:\Servio
# Copie instalar_pc_novo.ps1 para C:\Servio ou clone o repositório antes
Set-ExecutionPolicy -Scope Process Bypass -Force
.\instalar_pc_novo.ps1
```

O script faz automaticamente:

- Baixa o projeto do GitHub
- Copia para `C:\xampp\htdocs\advocacia`
- Configura Apache e MySQL como serviço (se possível)
- Importa o banco `backup_advocacia.sql`
- Libera portas no firewall

5. Acesse: **http://localhost/advocacia**

#### Opção 2 — Instalação manual passo a passo

**Passo 1 — XAMPP**

1. Baixe em https://www.apachefriends.org/
2. Instale em `C:\xampp`
3. Abra o XAMPP e clique **Start** em **MySQL** e **Apache**

**Passo 2 — Baixar o sistema**

```powershell
mkdir C:\Servio
cd C:\Servio
git clone https://github.com/Ricardo-Santos-Tostas/Galvao.git
```

**Passo 3 — Importar o banco**

1. Entre em `C:\Servio\Galvao\advocacia`
2. Execute **`instalar_novo_pc.bat`**
3. Aguarde 1–3 minutos

**Passo 4 — Copiar para o XAMPP**

Copie a pasta `advocacia` para:

```
C:\xampp\htdocs\advocacia
```

**Passo 5 — Primeira atualização com dados do servidor**

Na **Área de Trabalho**, execute como administrador:

```
baixar_atualizacao_cliente.bat
```

Isso sincroniza os cadastros do GitHub **sem apagar** fotos e documentos locais.

**Passo 6 — Acessar**

Abra: **http://localhost/advocacia**

---

### Checklist — PC novo

- [ ] XAMPP instalado
- [ ] MySQL ligado (verde no XAMPP)
- [ ] Apache ligado (se usar `localhost/advocacia`)
- [ ] Banco importado (`instalar_novo_pc.bat` ou script automático)
- [ ] Sistema abre no navegador
- [ ] Total de processos aparece no menu
- [ ] Backup automático instalado (ver seção 3)

---

## 2. Atualizar do seu PC para o PC do cliente

### Como funciona

| Seu PC | GitHub | PC do cliente |
|--------|--------|---------------|
| Você altera código e/ou cadastros | Recebe o `git push` | Roda `baixar_atualizacao_cliente.bat` |
| Exporta cadastros para CSV | Armazena `dados_servidor.csv` | Sincroniza (atualiza + insere novos) |

**O banco do cliente NÃO é apagado.**  
Fotos, documentos e anexos locais do cliente são **preservados**.

---

### No seu PC (antes de enviar)

#### Só mudou código (telas, relatórios, botões…)

```powershell
cd C:\Users\Ricardo\Desktop\Servio
git add .
git commit -m "Descreva o que mudou"
git push origin main
```

#### Mudou cadastros no banco (novos clientes, alterações…)

1. Ligue o **MySQL** no XAMPP
2. Execute:

```
Servio\advocacia\exportar_para_sync.bat
```

3. Envie para o Git:

```powershell
cd C:\Users\Ricardo\Desktop\Servio
git add advocacia/import/dados_servidor.csv
git add .
git commit -m "Atualiza cadastros para sincronizar no cliente"
git push origin main
```

> Rode o `exportar_para_sync.bat` **sempre** que alterar dados no seu MySQL antes de atualizar o cliente.

---

### No PC do cliente

1. Coloque na **Área de Trabalho** (se ainda não tiver):
   - `baixar_atualizacao_cliente.bat`
   - `baixar_atualizacao_cliente.ps1`

2. Clique com o botão direito em **`baixar_atualizacao_cliente.bat`**

3. Escolha **Executar como administrador**

4. Aguarde os 5 passos:

   | Passo | O que faz |
   |-------|-----------|
   | 1/5 | Baixa código do GitHub (`git pull`) |
   | 2/5 | Copia para `C:\xampp\htdocs\advocacia` |
   | 3/5 | Aplica migrações do banco (novas tabelas/colunas) |
   | 4/5 | Sincroniza `dados_servidor.csv` (sem apagar anexos) |
   | 5/5 | Reinicia Apache e verifica instalação |

5. No navegador: **Ctrl+F5** (limpa cache)

6. Acesse: **http://localhost/advocacia**

---

### Fluxo resumido

```
SEU PC                          GITHUB                    CLIENTE
  |                               |                          |
  |-- altera codigo --------------|                          |
  |-- exportar_para_sync.bat -----|  (se mudou cadastros)    |
  |-- git push ------------------>|                          |
  |                               |<-- baixar_atualizacao ---|
  |                               |     (pull + sync)        |
```

---

### Sincronização x substituição

| Ação | Apaga banco do cliente? | Quando usar |
|------|-------------------------|-------------|
| `baixar_atualizacao_cliente.bat` | **Não** | Atualização normal |
| `exportar_para_sync` + sync CSV | **Não** | Enviar cadastros do seu PC |
| `instalar_novo_pc.bat` / importar backup completo | **Sim** (substitui tudo) | Só em PC novo ou recuperação |

---

## 3. Backup do banco de dados

### Backup automático na Área de Trabalho (recomendado para o cliente)

Salva todo dia na pasta:

```
Área de Trabalho\backup-banco\
```

#### Instalar (uma vez)

1. Atualize o sistema (`baixar_atualizacao_cliente.bat`)
2. Na Área de Trabalho, execute como **Administrador**:

```
instalar_backup_cliente.bat
```

3. Pronto — backup **todo dia às 23:00**

#### O que é salvo

```
backup-banco\
  backup_advocacia_2026-07-02_23-00.sql   ← um arquivo por dia
  backup_advocacia.sql                     ← cópia mais recente
  backup.log                               ← histórico
```

- Mantém os **últimos 30 dias**
- Remove backups antigos automaticamente

#### Testar backup agora (manual)

Execute na Área de Trabalho:

```
backup_banco_cliente.bat
```

Ou direto na pasta do sistema:

```
C:\xampp\htdocs\advocacia\backup_banco_area_trabalho.bat
```

#### Importante

- O **MySQL precisa estar ligado** no horário do backup (23:00)
- Se o PC desliga à noite, altere o horário no **Agendador de Tarefas** do Windows:
  - Procure a tarefa: **Advocacia Backup Area de Trabalho**
  - Mude para um horário antes de desligar (ex.: 18:00)

---

### Backup dentro da pasta do sistema (alternativa)

Na pasta `advocacia`:

| Arquivo | Função |
|---------|--------|
| `backup_banco_automatico.bat` | Faz backup manual agora |
| `instalar_backup_automatico.bat` | Agenda diário em `advocacia\sql\backups\` |

---

### Backup manual completo (exportar)

```
advocacia\exportar_banco.bat
```

Gera: `advocacia\sql\backup_advocacia.sql`

Use quando quiser copiar o banco inteiro para outro PC ou pen drive.

---

### Restaurar um backup

1. Ligue o **MySQL** no XAMPP
2. Abra o **Prompt de Comando**
3. Execute (ajuste o caminho do arquivo):

```bat
C:\xampp\mysql\bin\mysql.exe -u root < "%USERPROFILE%\Desktop\backup-banco\backup_advocacia.sql"
```

> **Atenção:** restaurar um backup **substitui** o banco atual. Use só em emergência ou em PC novo.

---

## 4. Uso diário

### No seu PC (desenvolvimento)

1. Ligar **MySQL** no XAMPP
2. Executar `advocacia\iniciar.bat`
3. Acessar **http://localhost:8080**
4. Para parar: fechar a janela do `iniciar.bat` ou **Ctrl+C**

### No PC do cliente

1. Ligar **MySQL** e **Apache** no XAMPP
2. Acessar **http://localhost/advocacia**
3. Login com usuário e senha do escritório

### Outros PCs na mesma rede

No PC servidor, anote o IP (comando `ipconfig`) e nos outros PCs abra:

```
http://IP_DO_SERVIDOR:8080        (se usar iniciar.bat)
http://IP_DO_SERVIDOR/advocacia   (se usar Apache/XAMPP)
```

---

## 5. Arquivos importantes

### Na pasta Servio (raiz)

| Arquivo | Para quê |
|---------|----------|
| `INSTRUCOES_COMPLETAS.md` | Este guia |
| `baixar_atualizacao_cliente.bat` | Atualizar PC do cliente |
| `baixar_atualizacao_cliente.ps1` | Script interno da atualização |
| `instalar_backup_cliente.bat` | Instalar backup diário na Área de Trabalho |
| `backup_banco_cliente.bat` | Fazer backup agora na Área de Trabalho |
| `instalar_pc_novo.ps1` | Instalação automática em PC novo |
| `atualizar_sistema.bat` | Atualizar só código (sem sync de cadastros) |

### Na pasta advocacia

| Arquivo | Para quê |
|---------|----------|
| `iniciar.bat` | Iniciar sistema na porta 8080 |
| `instalar_novo_pc.bat` | Importar banco na primeira instalação |
| `exportar_para_sync.bat` | Exportar cadastros para o Git (seu PC) |
| `exportar_banco.bat` | Backup completo do MySQL |
| `backup_banco_area_trabalho.bat` | Backup na pasta backup-banco |
| `instalar_backup_area_trabalho.bat` | Agendar backup na Área de Trabalho |

---

## 6. O que não fazer

| Não faça | Motivo |
|----------|--------|
| Rodar `instalar_novo_pc.bat` no cliente para atualizar | Apaga e recria o banco |
| Importar `backup_advocacia.sql` no cliente com dados novos | Sobrescreve tudo |
| Apagar a pasta `advocacia` no XAMPP sem backup | Perde fotos/documentos locais |
| Esquecer o `exportar_para_sync.bat` antes do push | Cliente não recebe cadastros novos |
| Desligar o PC do cliente sem backup instalado | Risco de perda de dados |

---

## 7. Solução de problemas

| Problema | Solução |
|----------|---------|
| `ERR_CONNECTION_REFUSED` | Ligar MySQL/Apache ou executar `iniciar.bat` |
| Erro no `baixar_atualizacao_cliente.bat` | Executar como **Administrador**; verificar internet e Git |
| Lista de processos vazia | Rodar `instalar_novo_pc.bat` com MySQL ligado |
| Cliente sem cadastros novos | No seu PC: `exportar_para_sync.bat` → commit → push → atualizar cliente |
| Backup não criou arquivo | MySQL desligado; ligar no XAMPP e testar `backup_banco_cliente.bat` |
| Logo ou tela antiga após atualizar | **Ctrl+F5** no navegador |
| Porta 8080 ocupada | Fechar outras janelas do `iniciar.bat` |

### Configuração do banco (`advocacia/config/config.local.php`)

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

---

## Contato

**Moura Galvão Advogados Associados**  
Rua Miguel Calmon Nº 61 | Salvador-BA  
Tel. 71 3327.2299 | mouragalvaoadvogados@hotmail.com

---

*Última atualização: julho/2026*
