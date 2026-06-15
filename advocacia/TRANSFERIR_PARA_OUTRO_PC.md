# Transferir o sistema para outro computador

Guia rápido para levar o **Gestão de Processos Moura Galvão** para outro PC com **banco de dados incluído**.

---

## No PC ATUAL (onde o sistema já funciona)

### Passo 1 — Ligue o MySQL no XAMPP

### Passo 2 — Execute o preparador

Dê duplo clique em:

```
preparar_transferencia.bat
```

Isso vai:
- Exportar todo o banco MySQL para `sql/backup_advocacia.sql`
- Gerar o arquivo `LEIA-ME-TRANSFERENCIA.txt`

### Passo 3 — Copie a pasta inteira

Copie **toda a pasta `advocacia`** para pen drive, OneDrive ou rede:

```
C:\Users\Ricardo\Desktop\Servio\advocacia
```

**Não precisa** copiar `venv`, `sistema.db` ou `Pasta1.xlsx` — só a pasta `advocacia` com o backup SQL.

---

## No PC NOVO

### Passo 1 — Instalar XAMPP

Download: https://www.apachefriends.org/

Instale normalmente (PHP + MySQL).

### Passo 2 — Colar a pasta

Exemplo:

```
C:\Servio\advocacia
```

### Passo 3 — Ligar MySQL

Abra o **XAMPP** → **Start** no **MySQL**.

### Passo 4 — Instalar o sistema

Dê duplo clique em:

```
instalar_novo_pc.bat
```

Isso vai:
- Criar o banco `advocacia`
- Importar todos os ~16.876 registros
- Verificar se tudo está funcionando

### Passo 5 — Usar o sistema

Execute:

```
iniciar.bat
```

Abra no navegador:

```
http://localhost:8080
```

---

## Rede local (outros PCs)

No PC novo, o `iniciar.bat` mostra o IP para os outros computadores:

```
http://192.168.x.x:8080
```

Todos devem estar na **mesma rede Wi-Fi**.

---

## Arquivos importantes

| Arquivo | Função |
|---------|--------|
| `preparar_transferencia.bat` | Usar no PC **antigo** — exporta banco |
| `exportar_banco.bat` | Só exporta o MySQL |
| `instalar_novo_pc.bat` | Usar no PC **novo** — importa banco |
| `iniciar.bat` | Inicia o sistema |
| `sql/backup_advocacia.sql` | Cópia completa do banco |
| `config/config.local.php` | Configuração MySQL |

---

## Problemas comuns

| Problema | Solução |
|----------|---------|
| MySQL não conecta | Ligue MySQL no XAMPP |
| `backup_advocacia.sql` não existe | Rode `preparar_transferencia.bat` no PC antigo |
| Lista vazia após instalar | Rode `instalar_novo_pc.bat` de novo |
| Outros PCs não acessam | Firewall → liberar porta **8080** |

---

## Checklist

**PC antigo:**
- [ ] MySQL ligado
- [ ] `preparar_transferencia.bat` executado
- [ ] Pasta `advocacia` copiada (com `sql/backup_advocacia.sql`)

**PC novo:**
- [ ] XAMPP instalado
- [ ] Pasta `advocacia` colada
- [ ] MySQL ligado
- [ ] `instalar_novo_pc.bat` executado
- [ ] `iniciar.bat` funcionando
- [ ] `http://localhost:8080` abre o sistema
