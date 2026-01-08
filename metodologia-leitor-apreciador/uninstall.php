<?php
/**
 * Executado quando o plugin é desinstalado.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se uninstall não foi chamado pelo WordPress, abortar.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

/**
 * Limpar opções do plugin.
 * 
 * Nota: Os dados no Supabase NÃO são removidos automaticamente.
 * A remoção deve ser feita manualmente no painel do Supabase.
 */

// Remover opções do plugin
delete_option('mla_settings');
delete_option('mla_autosave_interval');
delete_option('mla_progressive_form');
delete_option('mla_step_texts');
delete_option('mla_submission_required');

// Remover post meta de todos os posts
delete_post_meta_by_key('_mla_enabled');
delete_post_meta_by_key('_mla_project_id');
delete_post_meta_by_key('_mla_text_id');

// Limpar transients
delete_transient('mla_projects_cache');
delete_transient('mla_texts_cache');
