# Holerite

Sistema completo de holerites desenvolvido em PHP para execução com XAMPP (Apache + MySQL).

## Pré-requisitos

- PHP 8.1 ou superior (incluso no XAMPP mais recente)
- MySQL/MariaDB
- Extensão PDO habilitada

## Instalação

1. Copie a pasta do projeto para o diretório `htdocs` do XAMPP.
2. Crie o banco de dados executando o script [`database/schema.sql`](database/schema.sql) no phpMyAdmin ou pelo MySQL CLI:

   ```sql
   SOURCE /caminho/para/database/schema.sql;
   ```

3. Ajuste as credenciais do banco em [`config/config.php`](config/config.php) caso necessário.
4. Acesse `http://localhost/holerite/public/` no navegador.

## Funcionalidades

- Cadastro completo de colaboradores (CRUD)
- Geração de holerites com proventos e descontos ilimitados
- Tipos especiais de holerite (mensal, férias com 1/3 constitucional e desligamento com cálculo de 13º proporcional)
- Cálculo automático de salário líquido
- Histórico de holerites e painel resumido
- Impressão em layout profissional inspirado em recibo padrão de holerite

## Estrutura

- `public/` — ponto de entrada da aplicação.
- `config/` — configurações do banco de dados e dados básicos da empresa.
- `src/` — código-fonte da aplicação (controllers, models, repositories, services e views).
- `database/` — scripts SQL auxiliares.

## Personalização

Os estilos básicos estão embutidos no layout principal em [`src/Views/layout/base.php`](src/Views/layout/base.php). Ajuste conforme a identidade visual da sua empresa. Os dados da empresa (nome, CNPJ, endereço e telefone) exibidos no holerite podem ser alterados em [`config/config.php`](config/config.php).

## Segurança

Esta aplicação é um exemplo educacional. Para uso em produção, considere adicionar autenticação, controles de acesso e logs de auditoria.
