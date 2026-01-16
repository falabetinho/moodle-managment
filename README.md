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

### 5. Personalizar Cores das Categorias
- Customizar cores dos cards por categoria
- Interface visual com preview de cores
- Suporte a dark mode automático

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

## Shortcodes Disponíveis

### [moodle_cursos]

Exibe a lista de cursos com filtros, busca e paginação.

**Atributos:**

- `category_id` (número) - Restringe a exibição a uma categoria específica. Padrão: `null` (todas as categorias)
- `show_subcategories` (true/false) - Mostra subcategorias da categoria selecionada. Padrão: `true`
- `show_title` (true/false) - Exibe um título acima dos cursos. Padrão: `false`
- `title` (texto) - Título customizado a exibir (requer `show_title="true"`). Se não informado, usa o título da página
- `color_scheme` (auto|category|custom) - Define o esquema de cores para os cards. Padrão: `auto`
  - `auto` - Gera cores automáticas baseadas no ID do curso (cores consistentes)
  - `category` - Usa cores baseadas na categoria do curso (em desenvolvimento)
  - `custom` - Usa cores customizadas do metadado do curso (em desenvolvimento)

**Exemplos:**

```
[moodle_cursos]
<!-- Exibe todos os cursos sem título -->

[moodle_cursos category_id="5"]
<!-- Exibe cursos da categoria 5 com suas subcategorias -->

[moodle_cursos category_id="5" show_subcategories="false"]
<!-- Exibe apenas cursos da categoria 5, sem subcategorias -->

[moodle_cursos show_title="true"]
<!-- Exibe todos os cursos com título (usa título da página) -->

[moodle_cursos show_title="true" title="Nossos Cursos Disponíveis"]
<!-- Exibe todos os cursos com título customizado -->

[moodle_cursos category_id="5" show_title="true" title="Cursos de Python"]
<!-- Exibe cursos da categoria 5 com título customizado -->

[moodle_cursos color_scheme="auto"]
<!-- Usa esquema de cores automático (padrão) -->
```

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
