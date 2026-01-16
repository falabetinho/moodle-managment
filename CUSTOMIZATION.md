# Guia de Personalização - Moodle Management

## Personalização de Cores dos Cards

### Sistema de Cores Atual

Os cards dos cursos utilizam um sistema de gradientes gerado automaticamente baseado no ID do curso. Isso garante que cada curso sempre tenha as mesmas cores de forma consistente.

### Opções de Personalização

#### 1. Esquema de Cores Automático (Padrão)

```
[moodle_cursos color_scheme="auto"]
```

Gera cores automáticas determinísticas baseadas no ID do curso usando a função `crc32()`. As cores são sempre as mesmas para o mesmo curso.

**Vantagens:**
- Cores consistentes e previsíveis
- Sem necessidade de configuração
- Visual diversificado e atraente

#### 1b. Admin Panel para Cores por Categoria

Vá até "Moodle Management > Cores das Categorias" para customizar as cores de cada categoria.

**Como funciona:**
1. Acesse o painel administrativo do WordPress
2. Vá para "Moodle Management" no menu lateral
3. Clique na aba "Cores das Categorias"
4. Para cada categoria, selecione Cor 1 e Cor 2
5. Clique em "Salvar Cores"
6. Use `[moodle_cursos color_scheme="category"]` para aplicar as cores da categoria

**Vantagens:**
- Interface visual intuitiva
- Preview em tempo real das cores
- Consistência por categoria
- Sem código necessário

#### 2. CSS Variables (Recomendado)

Você pode customizar as cores editando o arquivo `archive-cursos.php` e modificando as CSS variables:

```css
:root {
  --primary-color: #0078d4;
  --primary-light: #50e6ff;
  --success-color: #107c10;
  --warning-color: #ffb900;
  --danger-color: #da3b01;
  /* ... outras variáveis */
}
```

#### 3. Customização por Tema

Adicione CSS customizado no arquivo `functions.php` do seu tema:

```php
// functions.php
add_action('wp_enqueue_scripts', function() {
    wp_add_inline_style('moodle-cursos', '
        .course-card {
            --course-gradient: linear-gradient(135deg, #FF6B6B 0%, #FF8E72 100%);
        }
    ');
});
```

#### 4. Customização por Curso Individual

Para customizar cores de cursos individuais, adicione meta campos:

```php
// Adicionar cor customizada a um curso específico
update_post_meta($course_id, '_course_gradient_color1', '#FF6B6B');
update_post_meta($course_id, '_course_gradient_color2', '#FF8E72');
```

Depois, estenda a função `get_course_gradient()` em `class-moodle-courses.php`:

```php
public static function get_course_gradient($course_id, $color_scheme = 'auto') {
    if ($color_scheme === 'custom') {
        $color1 = get_post_meta($course_id, '_course_gradient_color1', true);
        $color2 = get_post_meta($course_id, '_course_gradient_color2', true);
        
        if ($color1 && $color2) {
            return sprintf('linear-gradient(135deg, %s 0%%, %s 100%%)', $color1, $color2);
        }
    }
    
    return self::generate_course_gradient($course_id, $color_scheme);
}
```

#### 5. Customização por Categoria

Para usar cores específicas por categoria:

```php
// Adicionar cor da categoria
update_term_meta($category_id, '_category_gradient_color1', '#0078d4');
update_term_meta($category_id, '_category_gradient_color2', '#50e6ff');
```

Depois, estenda a função `get_course_gradient()`:

```php
public static function get_course_gradient($course_id, $color_scheme = 'auto') {
    if ($color_scheme === 'category') {
        $course = get_post($course_id);
        $category_id = $course->category_id;
        
        $color1 = get_term_meta($category_id, '_category_gradient_color1', true);
        $color2 = get_term_meta($category_id, '_category_gradient_color2', true);
        
        if ($color1 && $color2) {
            return sprintf('linear-gradient(135deg, %s 0%%, %s 100%%)', $color1, $color2);
        }
    }
    
    return self::generate_course_gradient($course_id, $color_scheme);
}
```

### Paletas de Cores Recomendadas

#### Design Fluent (Padrão)
- Primário: #0078d4
- Secundário: #50e6ff

#### Tech/Dev
- Primário: #FF6B6B
- Secundário: #FF8E72

#### Business/Corporate
- Primário: #1A202C
- Secundário: #4A5568

#### Growth/Startup
- Primário: #10B981
- Secundário: #6EE7B7

#### Creative/Design
- Primário: #A855F7
- Secundário: #E879F9

### Modificar Efeitos Visuais

#### Animação do Background

No CSS, procure por `backgroundZoom`:

```css
@keyframes backgroundZoom {
    0% { transform: scale(1); }
    100% { transform: scale(1.05); }
}
```

Modifique a escala (1.05) para aumentar ou diminuir o efeito.

#### Blur Glass Morphism

Procure por `--glass-blur` e modifique:

```css
--glass-blur: blur(10px); /* Aumente para blur(20px) para mais efeito */
```

#### Sombras (Elevation)

Modifique os valores de shadow:

```css
--shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.08);
--shadow-md: 0 4px 12px rgba(0, 0, 0, 0.12);
--shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
--shadow-xl: 0 16px 40px rgba(0, 0, 0, 0.16);
```

### Dark Mode

O plugin já suporta dark mode automaticamente via `prefers-color-scheme: dark`.

Para customizar cores do dark mode:

```css
@media (prefers-color-scheme: dark) {
    body {
        --neutral-dark: #ffffff;
        --neutral-light: #2d2d2d;
        --neutral-surface: #1e1e1e;
    }
}
```

### Exemplo Completo de Customização

Adicione ao `functions.php` do seu tema:

```php
add_action('wp_enqueue_scripts', function() {
    $custom_css = '
        :root {
            /* Cores corporativas */
            --primary-color: #1B5E75;
            --primary-light: #2E8B9E;
            
            /* Ajustar gradiente dos cards */
            .course-card {
                --course-gradient: linear-gradient(135deg, #1B5E75 0%, #2E8B9E 100%);
            }
        }
        
        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            .course-card {
                --course-gradient: linear-gradient(135deg, #0F3A47 0%, #1B5E75 100%);
            }
        }
    ';
    
    wp_add_inline_style('moodle-cursos', $custom_css);
});
```

## Estrutura do HTML

Os cards agora têm a seguinte estrutura:

```html
<div class="course-card" style="--course-gradient: linear-gradient(...);">
    <div class="course-card-background">
        <!-- SVG com padrão geométrico -->
    </div>
    <div class="course-category-badge"><!-- Categoria --></div>
    <div class="course-header"><!-- Título --></div>
    <div class="course-content"><!-- Código --></div>
    <div class="course-footer"><!-- Preço --></div>
</div>
```

## Funções Disponíveis para Extensão

### `Moodle_Courses::generate_course_gradient($course_id, $color_scheme)`

Gera um gradiente de cores para um curso.

**Parâmetros:**
- `$course_id` (int): ID do curso do Moodle
- `$color_scheme` (string): Tipo de esquema ('auto', 'category', 'custom')

**Retorno:** String com CSS gradient

### `Moodle_Courses::get_course_gradient($course_id, $color_scheme)`

Obtém o gradiente final, tentando fontes customizadas antes de fallback.

## Próximas Melhorias Planejadas

- [x] Admin panel para customizar cores por categoria
- [ ] Admin field para customizar cores por curso
- [ ] Mais padrões geométricos customizáveis
- [ ] Prévia de cores ao customizar
- [ ] Exportar/importar temas de cores

