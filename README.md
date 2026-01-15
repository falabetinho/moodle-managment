# Moodle Management - Plugin WordPress

Plugin WordPress para gerenciar a integração com Moodle, permitindo sincronizar cursos, categorias e inscrições (enrollments).

## Funcionalidades

### 1. Configuração de Conexão
- Gerenciar configurações de conexão ao webservice do Moodle
- Armazenar URL base, usuário e token de acesso
- Testar conexão com o Moodle

### 2. Gerenciar Categorias
- Sincronizar categorias e subcategorias do Moodle
- Visualizar lista de categorias sincronizadas
- Tabela com informações de cada categoria

### 3. Importar Cursos
- Sincronizar cursos do Moodle
- Visualizar lista de cursos disponíveis
- Marcar status de importação dos cursos

### 4. Importar Enrollments
- Sincronizar inscrições de usuários em cursos
- Visualizar lista de enrollments
- Rastrear role e método de inscrição

## Estrutura do Plugin

```
moodle-managment/
├── moodle-management.php          # Arquivo principal do plugin
├── admin/
│   └── class-admin-tabs.php       # Interface de administração com abas
├── includes/
│   ├── class-moodle-management.php # Classe principal do plugin
│   └── class-moodle-api.php       # Integração com API do Moodle
├── assets/
│   ├── css/
│   │   └── admin.css              # Estilos da interface administrativa
│   └── js/
│       └── admin.js               # Scripts da interface administrativa
└── README.md                       # Este arquivo
```

## Instalação

1. Coloque a pasta do plugin em `/wp-content/plugins/`
2. Ative o plugin no painel administrativo do WordPress
3. Vá para "Moodle Management" no menu lateral
4. Configure as credenciais de conexão ao Moodle

## Configuração

Na aba "Configuração de Conexão":

1. **URL Base do Webservice**: URL do seu Moodle (ex: https://seu-moodle.com.br)
2. **Usuário**: Usuário com permissão de webservice no Moodle
3. **Token**: Token de acesso gerado no Moodle

Clique em "Testar Conexão" para validar as configurações.

## Bancos de Dados

O plugin cria as seguintes tabelas:

- `wp_moodle_settings` - Configurações de conexão
- `wp_moodle_categories` - Categorias sincronizadas do Moodle
- `wp_moodle_courses` - Cursos sincronizados do Moodle
- `wp_moodle_enrollments` - Inscrições sincronizadas do Moodle

## Requisitos

- WordPress 5.0+
- PHP 7.2+
- Acesso ao webservice REST do Moodle

## Segurança

- Todos os dados sensíveis são sanitizados
- Verificação de nonce em todas as requisições AJAX
- Verificação de permissões de administrador
- Token armazenado de forma segura no banco de dados

## Desenvolvimento

O plugin foi desenvolvido seguindo os padrões de desenvolvimento WordPress e está pronto para expansão.

### Próximos passos

- Implementação completa de sincronização de enrollments
- Mapeamento de usuários e cursos
- Interface melhorada para gerenciamento de dados

## Licença

GPL-2.0+

## Autor

Falabetinho

## Versão

1.0.0
