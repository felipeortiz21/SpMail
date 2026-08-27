# SpMail

Sistema de gerenciamento e envio de email marketing em PHP, mantido pela **Spiral Soluções e Consultoria LTDA**.

Este projeto é um fork do PortilloMail (código aberto, iniciado por Rodrigo Portillo em 2015), distribuído sob os termos da Mozilla Public License 2.0 (veja o arquivo `LICENSE`).

Contato: **contato@spiralsolucoes.com**

## O que é o SpMail?

O SpMail permite enviar campanhas de email em lote usando o endereço de email de sua preferência para envio e resposta, respeitando um limite configurável de emails por hora. É possível criar e gerenciar grupos, contatos e usuários do sistema, acompanhar cliques e visualizações de cada campanha, e retomar automaticamente um envio interrompido.

## Funcionalidades

- Editor HTML (TinyMCE) para composição dos emails
- Envio de email de teste antes do disparo real
- Envio automático por hora, respeitando limites diferentes em horário comercial e fora dele
- Disparo por grupo de contatos ou para todos os contatos autorizados
- **Variáveis dinâmicas** `{nome}`, `{email}` e `{telefone}` no assunto e no corpo do email, substituídas pelo dado real de cada contato no momento do envio (veja a seção [Variáveis dinâmicas](#variáveis-dinâmicas))
- Cadastro e gerenciamento de grupos e contatos, com importação em lote via CSV
- Cadastro e gerenciamento de usuários do sistema, com papel de **Administrador Geral**
- Painel de Configurações do Sistema (dados da empresa, SMTP, limites de envio, horário comercial), restrito ao Administrador Geral
- Uso de servidor SMTP externo (via PHPMailer) ou da função `mail()` nativa do PHP
- Retomada automática do envio em caso de interrupção, com um watchdog opcional via cron que retoma sozinho campanhas travadas (veja [Envio em lote e anti-spam](#envio-em-lote-e-anti-spam))
- Variação aleatória (jitter) configurável no intervalo entre emails e assinatura DKIM opcional, para reduzir a chance de cair em spam em campanhas grandes
- Acompanhamento de cliques e visualizações por campanha, com painel de estatísticas
- Página de descadastro automática para quem não quiser mais receber emails
- Geração automática de uma página HTML com o email completo, para quem não conseguir visualizar corretamente
- Verificação de formato de email e remoção automática de duplicados no envio

## Requisitos

- PHP 8.1 ou superior, com as extensões `mysqli`, `mbstring`, `curl`, `xml`, `zip` e `gd`
- MySQL 8 ou MariaDB equivalente
- Um servidor web (Nginx ou Apache) com PHP-FPM

## Instalação

### 1. Preparar o servidor

Em um servidor Ubuntu/Debian, clone o repositório e rode o instalador de servidor:

```bash
git clone https://github.com/felipeortiz21/SpMail.git
cd SpMail
sudo ./install.sh
```

O `install.sh`:

- verifica (e instala, com sua confirmação) PHP-FPM e as extensões necessárias;
- verifica (e instala, com sua confirmação) o MySQL, caso ainda não esteja presente;
- cria o banco de dados e um **usuário de banco dedicado** para a aplicação (a aplicação nunca usa o usuário `root` do MySQL);
- gera o arquivo `.env` com as credenciais e uma chave de criptografia própria (`APP_KEY`);
- ajusta as permissões dos arquivos para o usuário do servidor web.

Configure seu servidor web (Nginx/Apache) para apontar para a pasta `projeto/` do repositório clonado.

### 2. Concluir a instalação pelo navegador

Acesse `https://seudominio.com.br/instalador/` e siga o assistente:

1. Dados de conexão com o banco (o `install.sh` já deixa isso pronto, mas essa tela também funciona sozinha, sem precisar do `install.sh`, para quem só tem acesso via navegador/FTP);
2. Dados da empresa e do servidor SMTP;
3. Criação do primeiro usuário - que já nasce como Administrador Geral.

Ao final, a própria pasta `instalador/` é excluída automaticamente do servidor por segurança.

### Sem acesso root ao servidor?

Se você só tem acesso via navegador/FTP (hospedagem compartilhada, por exemplo), pule o `install.sh` e vá direto para `/instalador/` - basta ter um banco de dados e um usuário MySQL já criados pela sua hospedagem.

## Variáveis dinâmicas

Ao escrever o assunto ou o corpo do email (tela "Novo Email"), use os tokens abaixo em qualquer ponto do texto - eles são substituídos pelo dado de cada contato no momento do envio:

| Variável | Substituída por |
|---|---|
| `{nome}` | Nome do contato |
| `{email}` | Email do contato |
| `{telefone}` | Telefone do contato |

Um menu "Inserir Variável" acima do assunto e do editor insere o token na posição do cursor. Se o contato não tiver nome cadastrado, `{nome}` é removido do texto e a pontuação ao redor é ajustada automaticamente (ex: "Olá {nome}!" vira "Olá!" em vez de "Olá !").

## Envio em lote e anti-spam

O envio processa um contato por vez, respeitando o limite de "Emails por Hora" configurado, e se auto-agenda sozinho até terminar a campanha.

- **Variação aleatória (jitter)**: em Configurações, o campo "Variação Aleatória (%)" faz o intervalo entre um email e outro variar em vez de ser sempre idêntico (ex: 30% faz o intervalo oscilar entre 70% e 130% do valor calculado) - evita um padrão perfeitamente constante, que provedores como Gmail/Outlook associam a comportamento de bot.
- **DKIM (opcional)**: também em Configurações, é possível ativar a assinatura DKIM informando domínio, selector e a chave privada (formato PEM). Fica desligado por padrão - só ative se você já tiver a chave pública correspondente publicada no DNS do seu domínio.
- **Watchdog de retomada (cron)**: o envio se auto-continua via uma chamada interna do próprio servidor; se esse elo falhar por qualquer motivo (rede, processo morto, servidor reiniciado no meio de uma campanha), ela fica parada em "Enviando" até alguém clicar em "Continuar Envios" no painel. O `install.sh` oferece configurar uma tarefa cron (a cada 2 minutos) que detecta e retoma essas campanhas sozinho - script em `projeto/cron/retomar_envios.php`. Para adicionar manualmente depois:
  ```
  */2 * * * * php /var/www/SpMail/projeto/cron/retomar_envios.php >/dev/null 2>&1
  ```
- **Diagnóstico de falhas**: quando um envio individual falha, o motivo (retornado pelo PHPMailer) fica salvo em `restantes.erro_mensagem`, junto com a linha daquele contato/campanha.

## Administrador Geral

O primeiro usuário criado pelo instalador já recebe o papel de Administrador Geral. Esse papel é o único com acesso à tela de Configurações do Sistema e à gestão de usuários (criar, editar, desativar). Usuários comuns não veem esses itens no menu.

## Segurança

- Senhas de login usam `password_hash()`/`password_verify()` (bcrypt/argon2). Bases antigas com senha em MD5 continuam funcionando normalmente - o hash é atualizado automaticamente no primeiro login bem-sucedido, sem exigir troca de senha.
- A senha de envio SMTP é criptografada no banco de dados com a `APP_KEY` gerada na instalação.
- Todas as consultas ao banco usam prepared statements.
- Credenciais do banco e a chave de criptografia ficam em um arquivo `.env` (fora do controle de versão), não hardcoded no código.

## Estrutura do banco de dados

O schema completo está em `projeto/instalador/modelo_banco.sql` - é a única fonte de verdade usada pelo instalador. Todas as tabelas usam InnoDB e utf8mb4 (suporte completo a acentuação, emojis e caracteres especiais nas campanhas).

## Suporte

Para dúvidas ou sugestões, entre em contato: **contato@spiralsolucoes.com**
