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

Execute o seguinte SQL no SQL Editor do Supabase:

```sql
-- Tabela de Projetos
CREATE TABLE projects (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    name TEXT NOT NULL,
    description TEXT,
    status TEXT DEFAULT 'active',
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- Tabela de Textos (posts WP com metodologia ativa)
CREATE TABLE texts (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    wp_post_id BIGINT NOT NULL,
    project_id UUID REFERENCES projects(id) ON DELETE SET NULL,
    title TEXT,
    created_at TIMESTAMPTZ DEFAULT now()
);

-- Índice para busca por post_id
CREATE INDEX idx_texts_wp_post_id ON texts(wp_post_id);

-- Tabela de Respostas
CREATE TABLE responses (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    text_id UUID REFERENCES texts(id) ON DELETE CASCADE,
    wp_user_id BIGINT NOT NULL,
    wp_user_email TEXT,
    project_id UUID REFERENCES projects(id) ON DELETE SET NULL,
    status TEXT DEFAULT 'draft',
    version INTEGER DEFAULT 1,
    data JSONB NOT NULL DEFAULT '{}',
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- Índices para busca
CREATE INDEX idx_responses_text_id ON responses(text_id);
CREATE INDEX idx_responses_wp_user_id ON responses(wp_user_id);
CREATE INDEX idx_responses_status ON responses(status);

-- Tabela de Histórico de Versões
CREATE TABLE response_history (
    id UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    response_id UUID REFERENCES responses(id) ON DELETE CASCADE,
    version INTEGER NOT NULL,
    data JSONB NOT NULL,
    submitted_at TIMESTAMPTZ DEFAULT now()
);

CREATE INDEX idx_response_history_response_id ON response_history(response_id);
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
