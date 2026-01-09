# Metodologia Leitor-Apreciador

Plugin WordPress para implementar a Metodologia do Leitor-Apreciador, permitindo que leitores logados respondam a formulários reflexivos estruturados ao final de textos selecionados, com integração Supabase para armazenamento externo.

## Requisitos

- **PHP:** 7.4.33 ou superior (compatível com PHP 8.x)
- **WordPress:** 6.5.7 ou superior
- **Supabase:** Conta ativa com projeto configurado

## Instalação

1. Faça upload da pasta `metodologia-leitor-apreciador` para `/wp-content/plugins/`
2. Ative o plugin através do menu 'Plugins' no WordPress
3. Configure as credenciais do Supabase no `wp-config.php`

## Configuração do Supabase

### 1. Configurar no Painel Admin (Recomendado)

Após ativar o plugin:
1. Vá em **Leitor-Apreciador > Configurações**.
2. Role até a seção "Integração Supabase".
3. Preencha os campos `URL do Projeto`, `Anon Public Key` e opcionalmente `Service Role Key`.
4. Salve as alterações.

### 2. Configurar via wp-config.php (Opcional/Override)

Você também pode definir as constantes no `wp-config.php`. Se definidas, elas terão prioridade sobre as configurações do painel.

```php
// Metodologia Leitor-Apreciador - Supabase
define( 'MLA_SUPABASE_URL', 'https://seu-projeto.supabase.co' );
define( 'MLA_SUPABASE_ANON_KEY', 'sua-chave-anon-publica' );
define( 'MLA_SUPABASE_SERVICE_KEY', 'sua-chave-service-role' ); // Para operações admin
```

### 2. Criar tabelas no Supabase

Execute o seguinte SQL no SQL Editor do Supabase para configurar toda a estrutura necessária:

```sql
-- 1. Tabela de Projetos
CREATE TABLE IF NOT EXISTS public.projects (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    description TEXT,
    status TEXT DEFAULT 'active' CHECK (status IN ('active', 'archived')),
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- 2. Tabela de Textos (Sincronização com Posts WP)
CREATE TABLE IF NOT EXISTS public.texts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wp_post_id BIGINT UNIQUE NOT NULL,
    title TEXT NOT NULL,
    project_id UUID REFERENCES public.projects(id) ON DELETE SET NULL,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- 3. Tabela de Respostas
CREATE TABLE IF NOT EXISTS public.responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    text_id UUID NOT NULL REFERENCES public.texts(id) ON DELETE CASCADE,
    wp_user_id BIGINT NOT NULL,
    wp_user_email TEXT,
    project_id UUID REFERENCES public.projects(id) ON DELETE SET NULL,
    data JSONB NOT NULL DEFAULT '{}'::jsonb,
    draft_data JSONB DEFAULT NULL,
    status TEXT DEFAULT 'draft' CHECK (status IN ('draft', 'submitted')),
    version INTEGER DEFAULT 1,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now(),
    UNIQUE(wp_user_id, text_id)
);

-- 4. Tabela de Histórico de Versões
CREATE TABLE IF NOT EXISTS public.response_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    response_id UUID NOT NULL REFERENCES public.responses(id) ON DELETE CASCADE,
    version INTEGER NOT NULL,
    data JSONB NOT NULL,
    submitted_at TIMESTAMPTZ DEFAULT now()
);

-- 5. Tabela de Análises de IA (OpenRouter)
CREATE TABLE IF NOT EXISTS public.ai_analyses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    text_id UUID NOT NULL REFERENCES public.texts(id) ON DELETE CASCADE,
    content TEXT NOT NULL,
    model TEXT NOT NULL,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- Índices para Performance
CREATE INDEX IF NOT EXISTS idx_texts_wp_post_id ON public.texts(wp_post_id);
CREATE INDEX IF NOT EXISTS idx_responses_text_id ON public.responses(text_id);
CREATE INDEX IF NOT EXISTS idx_responses_wp_user_id ON public.responses(wp_user_id);
CREATE INDEX IF NOT EXISTS idx_response_history_response_id ON public.response_history(response_id);
CREATE INDEX IF NOT EXISTS idx_ai_analyses_text_id ON public.ai_analyses(text_id);
```

### 3. Configurar Row Level Security (RLS) - Opcional

Para maior segurança, configure RLS nas tabelas:

```sql
-- Habilitar RLS
ALTER TABLE responses ENABLE ROW LEVEL SECURITY;

-- Política: usuários só veem suas próprias respostas
CREATE POLICY "Users can view own responses"
ON responses FOR SELECT
USING (auth.uid()::text = wp_user_id::text);

-- Política: usuários só podem inserir suas próprias respostas
CREATE POLICY "Users can insert own responses"
ON responses FOR INSERT
WITH CHECK (auth.uid()::text = wp_user_id::text);

-- Política: usuários só podem atualizar suas próprias respostas
CREATE POLICY "Users can update own responses"
ON responses FOR UPDATE
USING (auth.uid()::text = wp_user_id::text);
```

> **Nota:** O RLS é opcional. Se você usar apenas a chave `service_role` para operações admin, as políticas não serão aplicadas.

## Uso

### Ativar Metodologia em um Post/Página

1. Edite um post ou página
2. Na metabox lateral "Metodologia do Leitor-Apreciador":
   - Marque "Ativar Metodologia do Leitor-Apreciador"
   - Opcionalmente, selecione um projeto vinculado
3. Publique/atualize o post

### Gerenciar no Admin

Acesse o menu **Leitor-Apreciador** no admin:

- **Dashboard:** Visão geral com estatísticas
- **Projetos:** Criar e gerenciar projetos
- **Textos:** Ver posts com metodologia ativa
- **Respostas:** Visualizar, filtrar e exportar respostas
- **Configurações:** Ajustar comportamento do formulário

### Exportar Dados

Na página de Respostas, use os botões:
- **Exportar CSV:** Planilha para análise
- **Exportar JSON:** Dados estruturados

## Estrutura do Plugin

```
metodologia-leitor-apreciador/
├── metodologia-leitor-apreciador.php  # Bootstrap
├── uninstall.php                       # Limpeza
├── includes/                           # Classes core
├── admin/                              # Admin classes e templates
├── frontend/                           # Frontend classes e templates
├── services/                           # Serviços (Supabase, etc.)
├── assets/                             # CSS e JavaScript
└── languages/                          # Traduções
```

## Hooks Disponíveis

### Actions

- `mla_before_save_response` - Antes de salvar resposta
- `mla_after_save_response` - Após salvar resposta
- `mla_before_submit_response` - Antes de submeter
- `mla_after_submit_response` - Após submeter

### Filters

- `mla_form_steps` - Modificar etapas do formulário
- `mla_autosave_interval` - Alterar intervalo de auto-save
- `mla_export_data` - Modificar dados antes da exportação

## Contribuição

Pull requests são bem-vindos. Para mudanças maiores, abra uma issue primeiro.

## Licença

[GPL v2 ou posterior](https://www.gnu.org/licenses/gpl-2.0.html)
