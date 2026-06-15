# Advocacia Trabalhista — Moura Galvão Advogados

Sistema web de gestão de processos jurídicos (PHP + MySQL + JavaScript).

## Início rápido (já instalado)

1. Ligue o **MySQL** no XAMPP
2. Execute **`iniciar.bat`**
3. Acesse **http://localhost:8080**

## Primeira instalação no PC novo

Siga o guia completo no **[README.md](../README.md)** na raiz do repositório.

Resumo:

1. Instalar [XAMPP](https://www.apachefriends.org/)
2. Baixar o projeto: `git clone https://github.com/Ricardo-Santos-Tostas/Galvao.git`
3. Ligar **MySQL** no XAMPP
4. Executar **`instalar_novo_pc.bat`** (importa o banco)
5. Executar **`iniciar.bat`**

## Scripts úteis

| Arquivo | Função |
|---------|--------|
| `instalar_novo_pc.bat` | Instala no PC novo (rodar 1x) |
| `iniciar.bat` | Inicia o sistema (uso diário) |
| `preparar_transferencia.bat` | Exporta banco no PC antigo |
| `exportar_banco.bat` | Só exporta o MySQL |
| `migrar.bat` | Migra `sistema.db` → MySQL |

## Banco de dados

- **MySQL** — banco `advocacia`, tabela `planilha1`
- Backup: `sql/backup_advocacia.sql`
- Configuração: `config/config.local.php`

## Documentação

- **[README.md](../README.md)** — instalação e transferência passo a passo
- **[DOCUMENTACAO.md](DOCUMENTACAO.md)** — documentação técnica completa
- **[TRANSFERIR_PARA_OUTRO_PC.md](TRANSFERIR_PARA_OUTRO_PC.md)** — guia resumido de transferência

## Tecnologias

PHP 8 · MySQL/MariaDB · HTML · CSS · JavaScript · XAMPP
