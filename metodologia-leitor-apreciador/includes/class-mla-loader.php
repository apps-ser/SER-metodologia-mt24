<?php
/**
 * Classe responsável por registrar e executar todos os hooks do plugin.
 *
 * @package MetodologiaLeitorApreciador
 */

// Se este arquivo for chamado diretamente, abortar.
if (!defined('WPINC')) {
    die;
}

/**
 * Class MLA_Loader
 *
 * Mantém listas de todos os hooks registrados pelo plugin e os executa
 * quando o WordPress é inicializado.
 */
class MLA_Loader
{

    /**
     * Array de actions registradas.
     *
     * @var array
     */
    protected $actions;

    /**
     * Array de filters registrados.
     *
     * @var array
     */
    protected $filters;

    /**
     * Inicializa as coleções de hooks.
     */
    public function __construct()
    {
        $this->actions = array();
        $this->filters = array();
    }

    /**
     * Adiciona uma nova action à coleção.
     *
     * @param string $hook          Nome do hook do WordPress.
     * @param object $component     Instância do objeto contendo o callback.
     * @param string $callback      Nome do método a ser executado.
     * @param int    $priority      Prioridade do hook. Default: 10.
     * @param int    $accepted_args Número de argumentos aceitos. Default: 1.
     *
     * @return void
     */
    public function add_action($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->actions = $this->add($this->actions, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Adiciona um novo filter à coleção.
     *
     * @param string $hook          Nome do hook do WordPress.
     * @param object $component     Instância do objeto contendo o callback.
     * @param string $callback      Nome do método a ser executado.
     * @param int    $priority      Prioridade do hook. Default: 10.
     * @param int    $accepted_args Número de argumentos aceitos. Default: 1.
     *
     * @return void
     */
    public function add_filter($hook, $component, $callback, $priority = 10, $accepted_args = 1)
    {
        $this->filters = $this->add($this->filters, $hook, $component, $callback, $priority, $accepted_args);
    }

    /**
     * Método utilitário para registrar hooks.
     *
     * @param array  $hooks         Array de hooks existentes.
     * @param string $hook          Nome do hook.
     * @param object $component     Instância do componente.
     * @param string $callback      Método callback.
     * @param int    $priority      Prioridade.
     * @param int    $accepted_args Argumentos aceitos.
     *
     * @return array Array de hooks atualizado.
     */
    private function add($hooks, $hook, $component, $callback, $priority, $accepted_args)
    {
        $hooks[] = array(
            'hook' => $hook,
            'component' => $component,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );

        return $hooks;
    }

    /**
     * Registra os filters e actions com o WordPress.
     *
     * @return void
     */
    public function run()
    {
        foreach ($this->filters as $hook) {
            add_filter(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }

        foreach ($this->actions as $hook) {
            add_action(
                $hook['hook'],
                array($hook['component'], $hook['callback']),
                $hook['priority'],
                $hook['accepted_args']
            );
        }
    }
}
