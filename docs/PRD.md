# PRD – Plugin WordPress

## Metodologia Leitor‑Apreciador (integração com Supabase)

---

## 1. Visão Geral

Criar um **plugin WordPress** que permita aplicar a **Metodologia do Leitor‑Apreciador** a qualquer post ou página, coletando respostas estruturadas dos leitores e armazenando esses dados em um **banco externo (Supabase)**.

O plugin deve possibilitar:

* Ativar ou não a metodologia em cada conteúdo individual.
* Exibir automaticamente um **formulário estruturado** ao final do texto.
* Exigir **login do usuário** para participação.
* Permitir que o leitor **consulte e edite suas próprias respostas**.
* Disponibilizar um **painel administrativo** para análise, organização e exportação das respostas.
* Agrupar conteúdos por **Projeto** (ex: Projeto Mateus 24) ou permitir análise de textos avulsos.

O plugin deve ser genérico, reutilizável e aplicável a qualquer projeto editorial que utilize a metodologia.

---

## 2. Objetivos do Produto

### 2.1 Objetivo Principal

Implementar tecnicamente a metodologia proposta no Projeto Mateus 24, transformando-a em um **processo estruturado, rastreável e analisável**, sem perder o caráter reflexivo e colaborativo.

### 2.2 Objetivos Secundários

* Padronizar a coleta de dados qualitativos dos leitores.
* Facilitar a análise comparativa entre leitores, textos e projetos.
* Criar base estruturada para futuras análises, visualizações ou IA.
* Garantir autoria, rastreabilidade e versionamento das respostas.

---

## 3. Escopo

### 3.1 Dentro do Escopo

* Plugin WordPress (PHP + JS)
* Integração com Supabase (REST ou SDK JS)
* Interface administrativa no WP
* Formulário dinâmico baseado na metodologia
* Controle de acesso por autenticação WordPress

### 3.2 Fora do Escopo (Fase Inicial)

* Respostas mediúnicas
* Workflow espiritual/editorial
* Dashboards avançados (BI)
* IA de análise semântica (futuro)

---

## 4. Personas

### 4.1 Leitor‑Apreciador

* Usuário registrado no WordPress
* Lê o texto e responde à metodologia
* Pode salvar rascunho
* Pode editar respostas após submissão

### 4.2 Administrador / Curador do Projeto

* Cria projetos
* Vincula posts/páginas aos projetos
* Analisa respostas
* Exporta dados

---

## 5. Conceitos‑Chave

### 5.1 Projeto

Entidade lógica que agrupa vários textos sob um mesmo estudo.

Exemplos:

* Projeto Mateus 24
* Estudo Sermão do Monte
* Textos Avulsos

### 5.2 Texto

Qualquer post ou página do WordPress que tenha a metodologia ativada.

### 5.3 Resposta do Leitor‑Apreciador

Conjunto estruturado de campos preenchidos conforme a metodologia.

---

## 6. Funcionalidades

---

## 6A. Experiência do Usuário (UX) do Formulário do Leitor-Apreciador

### 6A.1 Princípios de UX

A experiência do formulário deve respeitar a natureza **reflexiva, não apressada e investigativa** da metodologia. O UX deve:

* Reduzir sensação de formulário longo ou burocrático
* Incentivar reflexão progressiva
* Evitar perda de conteúdo
* Estimular qualidade, não quantidade
* Transmitir acolhimento e seriedade

---

### 6A.2 Formulário Progressivo (Step-by-Step)

O formulário será apresentado em **etapas sequenciais**, preferencialmente com navegação lateral ou barra de progresso.

Etapas sugeridas:

**Etapa 1 – Compreensão Geral**

* Tema Central
* Temas Secundários

**Etapa 2 – Conexões Doutrinárias**

* Correlação com outros textos / doutrina / evangelho

**Etapa 3 – Avaliação do Texto**

* Aspectos Positivos do Texto

**Etapa 4 – Investigação Crítica**

* Dúvidas Identificadas

**Etapa 5 – Formulação Consciente**

* Perguntas Formuladas a partir das Dúvidas

Cada etapa deve conter:

* Título explicativo
* Texto curto orientador (pedagógico)
* Campo(s) de resposta

---

### 6A.3 Barra de Progresso

* Indicador visual de progresso (ex: 1 de 5)
* Não usar linguagem de "conclusão obrigatória"
* Exibir mensagens como:

  * "Etapa 2 de 5 – Conectando ideias"

---

### 6A.4 Salvamento Automático (Auto-save)

O formulário deve implementar **salvamento automático contínuo**, com as seguintes regras:

* Auto-save a cada:

  * mudança de campo
  * ou intervalo configurável (ex: 15–30 segundos)

* Estado salvo como:

  * "rascunho"

* Indicador visual discreto:

  * "Rascunho salvo automaticamente"

* Nenhuma submissão final ocorre sem ação explícita do usuário

---

### 6A.5 Retomada de Preenchimento

* Ao retornar ao texto:

  * O sistema detecta resposta existente
  * Carrega automaticamente o último rascunho ou versão submetida

* Exibir mensagem:

  * "Você já iniciou sua apreciação deste texto. Deseja continuar?"

---

### 6A.6 Submissão Consciente

Antes da submissão final:

* Exibir um resumo das respostas
* Mensagem de consciência:

  * "Suas respostas poderão ser analisadas de forma coletiva e comparativa dentro do projeto"

Botões:

* "Voltar e revisar"
* "Submeter apreciação"

Após submissão:

* Status passa para "submetida"
* Edição permanece permitida

---

### 6A.7 Edição Pós-Submissão

* Usuário pode editar respostas mesmo após submissão
* Cada edição:

  * incrementa versão lógica
  * mantém histórico no Supabase

Exibir aviso:

* "Esta edição substituirá a versão anterior para fins de análise"

---

### 6A.8 Feedback Visual e Emocional

* Evitar tons avaliativos ("certo/errado")
* Usar linguagem neutra e acolhedora

Exemplos:

* "Sua reflexão foi registrada"
* "Você pode retornar e aprofundar quando desejar"

---

### 6A.9 Acessibilidade

* Campos grandes (textarea)
* Suporte a teclado
* Contraste adequado
* Compatível com leitores de tela

---

### 6A.10 Configurações de UX (Admin)

Permitir ao administrador:

* Ativar/desativar formulário progressivo
* Ajustar intervalo de auto-save
* Editar textos orientadores das etapas
* Definir se submissão é obrigatória ou opcional

---

### 6.1 Ativação da Metodologia no Editor

No editor de post/página (**sem dependência do Gutenberg**):

* Utilizar **Metabox clássica (add_meta_box)** compatível com:

  * Editor Clássico
  * Editor de Blocos (apenas como container, sem APIs do Gutenberg)

A metabox deve conter:

* Checkbox: "Ativar Metodologia do Leitor‑Apreciador"
* Select: "Projeto vinculado" (opcional)
* Campo oculto: Identificador interno do texto (auto)

Se não marcado:

* Nenhuma alteração no front‑end

Se marcado:

* Formulário é exibido automaticamente ao final do conteúdo

---

### 6.2 Formulário do Leitor‑Apreciador (Front‑end)

#### 6.2.1 Pré‑condições

* Usuário deve estar **logado**
* Caso não esteja:

  * Exibir mensagem: "Faça login para participar do estudo"

#### 6.2.2 Campos do Formulário

Campos baseados na metodologia:

1. Tema Central (textarea)
2. Temas Secundários (textarea)
3. Correlação com outros textos/doutrina (textarea)
4. Aspectos Positivos do Texto (textarea)
5. Dúvidas Identificadas (textarea)
6. Perguntas Formuladas a partir das Dúvidas (textarea)

Campos técnicos (ocultos):

* user_id (WP)
* user_email
* post_id
* project_id (opcional)
* data_hora
* versão_da_resposta

---

### 6.3 Salvamento e Edição das Respostas

* Ao salvar:

  * Resposta é enviada ao Supabase
  * Estado: "submetida"

* Usuário pode:

  * Revisitar o texto
  * Visualizar sua resposta
  * Editar e salvar novamente

Regras:

* Um usuário → uma resposta por texto
* Edição gera nova versão (versionamento lógico)

---

### 6.4 Painel Administrativo (WordPress)

Menu: "Leitor‑Apreciador"

#### 6.4.1 Submenus

* Projetos
* Textos
* Respostas
* Configurações

---

### 6.5 Gestão de Projetos

* Criar / editar / excluir projetos
* Campos do projeto:

  * Nome
  * Descrição
  * Status (ativo / arquivado)

---

### 6.6 Visualização de Respostas (Admin)

Filtros:

* Por projeto
* Por texto
* Por usuário
* Por data

Visualização:

* Lista
* Detalhe individual
* Comparação entre respostas

Exportação:

* CSV
* JSON

---

## 7. Integração com Supabase

### 7.1 Papel do Supabase

* Armazenar respostas
* Permitir escalabilidade
* Facilitar análises futuras

### 7.2 Estrutura de Tabelas (Sugestão)

**projects**

* id
* name
* description
* created_at

**texts**

* id
* wp_post_id
* project_id
* title

**responses**

* id
* user_id
* user_email
* post_id
* project_id
* data (JSON)
* version
* created_at
* updated_at

---

## 8. Segurança e Permissões

* Apenas usuários logados podem responder
* Usuário só edita suas próprias respostas
* Admin visualiza todas
* Supabase com RLS (Row Level Security)
* Chaves protegidas via wp-config.php

---

## 9. Requisitos Não‑Funcionais

### 9.1 Compatibilidade de Plataforma

* **WordPress mínimo suportado:** 6.5.7
* Compatível com versões superiores estáveis do WordPress
* **Não depender do Gutenberg** para funcionamento
* Utilizar apenas APIs estáveis do WordPress Core
* Compatível com:

  * Editor Clássico
  * Editor de Blocos (sem uso de APIs específicas do Gutenberg)

### 9.2 Requisitos Técnicos

* **PHP mínimo suportado:** 7.4.33
* Compatível com versões superiores do PHP (8.x)
* Evitar recursos exclusivos de PHP 8 (union types, attributes, etc.)
* Código orientado a hooks (actions / filters)
* Metaboxes via add_meta_box
* Front-end desacoplado do editor
* Código modular e extensível
* Logs de erro
* Internacionalização (i18n)

---

## 10. Métricas de Sucesso

* % de textos com metodologia ativada
* % de leitores participantes
* Número médio de respostas por texto
* Tempo médio de preenchimento

---

## 11. Evoluções Futuras

* Dashboard analítico
* IA para síntese das respostas
* Geração automática de perguntas consolidadas
* Integração com workflows editoriais
* Exportação direta para ferramentas de pesquisa

---

## 12. Observações Finais

Este plugin não é apenas técnico: ele é um **instrumento metodológico e espiritual**, e deve preservar:

* Neutralidade
* Liberdade de reflexão
* Rigor na organização dos dados

O código deve refletir esse cuidado.
