<?php
/**
 * Classe responsável pela interface administrativa e AJAX do plugin
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ISD_Admin {

    /**
     * Instância da classe de limpeza
     *
     * @var ISD_Cleanup
     */
    private $cleanup;

    /**
     * Construtor
     */
    public function __construct( $cleanup ) {
        $this->cleanup = $cleanup;

        // Hooks administrativos
        add_action( 'admin_menu', array( $this, 'add_settings_menu' ) );
        add_action( 'admin_init', array( $this, 'save_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        // AJAX para limpeza interativa
        add_action( 'wp_ajax_isd_get_cleanup_stats', array( $this, 'ajax_get_cleanup_stats' ) );
        add_action( 'wp_ajax_isd_run_cleanup_step', array( $this, 'ajax_run_cleanup_step' ) );
    }

    /**
     * Adiciona a página de configurações ao menu "Configurações"
     */
    public function add_settings_menu() {
        add_options_page(
            'ISD - Image Simple Destruction',
            'Image Destruction',
            'manage_options',
            'isd-settings',
            array( $this, 'render_settings_page' )
        );
    }

    /**
     * Renderiza o arquivo da página de configurações
     */
    public function render_settings_page() {
        // Carrega as configurações atuais para o template
        $settings = $this->cleanup->get_settings();
        $history  = get_option( 'isd_cleanup_history', array() );

        // Caminho do template
        include ISD_PATH . 'admin/settings-page.php';
    }

    /**
     * Enfileira CSS e scripts JavaScript no painel de administração
     */
    public function enqueue_admin_assets( $hook ) {
        if ( 'settings_page_isd-settings' !== $hook ) {
            return;
        }

        // Script AJAX personalizado para controle da limpeza manual
        wp_enqueue_script(
            'isd-admin-js',
            ISD_URL . 'admin/js/admin.js',
            array( 'jquery' ),
            ISD_VERSION,
            true
        );

        // Passa variáveis necessárias para o JS
        wp_localize_script(
            'isd-admin-js',
            'isd_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'isd_cleanup_nonce' ),
                'messages' => array(
                    'scanning'   => 'Escaneando mídias no banco de dados...',
                    'deleting'   => 'Excluindo imagens... {percent}% completado.',
                    'completed'  => 'Limpeza concluída com sucesso!',
                    'no_images'  => 'Nenhuma imagem encontrada para os critérios selecionados.',
                    'error'      => 'Ocorreu um erro no processo de exclusão.',
                    'confirm'    => 'Tem certeza de que deseja apagar permanentemente todas as imagens correspondentes? Essa ação não pode ser desfeita.',
                ),
            )
        );
    }

    /**
     * Salva as configurações enviadas pelo formulário
     */
    public function save_settings() {
        if ( ! isset( $_POST['isd_settings_submit'] ) ) {
            return;
        }

        // Verifica permissões e nonce
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Sem permissão.' );
        }

        check_admin_referer( 'isd_save_settings_action', 'isd_settings_nonce' );

        $old_settings = $this->cleanup->get_settings();

        // Sanitização dos dados
        $new_settings = array(
            'enabled'                   => isset( $_POST['enabled'] ) ? 1 : 0,
            'threshold_value'           => max( 1, (int) $_POST['threshold_value'] ),
            'threshold_unit'            => in_array( $_POST['threshold_unit'], array( 'days', 'months' ), true ) ? $_POST['threshold_unit'] : 'days',
            'delete_orphaned'           => isset( $_POST['delete_orphaned'] ) ? 1 : 0,
            'delete_trash_attachments'  => isset( $_POST['delete_trash_attachments'] ) ? 1 : 0,
            'delete_broken_parent'      => isset( $_POST['delete_broken_parent'] ) ? 1 : 0,
            'cron_interval'             => in_array( $_POST['cron_interval'], array( 'daily', 'twicedaily', 'weekly' ), true ) ? $_POST['cron_interval'] : 'daily',
            'exclude_categories'        => isset( $_POST['exclude_categories'] ) && is_array( $_POST['exclude_categories'] ) ? array_map( 'intval', $_POST['exclude_categories'] ) : array(),
        );

        update_option( 'isd_settings', $new_settings );

        // Gerencia o agendamento Cron conforme a mudança de status/intervalo
        if ( $new_settings['enabled'] ) {
            isd_schedule_cleanup_cron( $new_settings['cron_interval'] );
        } else {
            isd_unschedule_cleanup_cron();
        }

        // Redireciona com flag de sucesso
        wp_safe_redirect( add_query_arg( array( 'page' => 'isd-settings', 'settings-updated' => 'true' ), admin_url( 'options-general.php' ) ) );
        exit;
    }

    /**
     * AJAX Endpoint: Obter contagem de mídias pendentes de limpeza
     */
    public function ajax_get_cleanup_stats() {
        check_ajax_referer( 'isd_cleanup_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Acesso negado.' ) );
        }

        // Obtém contagem
        $old_ids = $this->cleanup->get_old_images_ids();
        $broken_ids = $this->cleanup->get_broken_images_ids();
        $all_ids = array_unique( array_merge( $old_ids, $broken_ids ) );

        // Calcula tamanho aproximado de arquivos
        $total_bytes = 0;
        // Pega uma amostra ou calcula de todos se não forem muitos para não estourar tempo
        $calc_limit = min( count( $all_ids ), 200 );
        $sample_bytes = 0;
        for ( $i = 0; $i < $calc_limit; $i++ ) {
            $sample_bytes += $this->cleanup->get_attachment_size( $all_ids[ $i ] );
        }

        if ( count( $all_ids ) > 0 && $calc_limit > 0 ) {
            $average_size = $sample_bytes / $calc_limit;
            $total_bytes = $average_size * count( $all_ids );
        }

        wp_send_json_success( array(
            'old_count'       => count( $old_ids ),
            'broken_count'    => count( $broken_ids ),
            'total_count'     => count( $all_ids ),
            'estimated_size'  => size_format( $total_bytes, 2 ),
            'ids'             => $all_ids,
        ) );
    }

    /**
     * AJAX Endpoint: Executa a remoção física de um lote (batch) de imagens
     */
    public function ajax_run_cleanup_step() {
        check_ajax_referer( 'isd_cleanup_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => 'Acesso negado.' ) );
        }

        $ids = isset( $_POST['ids'] ) ? array_map( 'intval', $_POST['ids'] ) : array();

        if ( empty( $ids ) ) {
            wp_send_json_success( array(
                'completed'   => true,
                'count'       => 0,
                'bytes_saved' => 0,
            ) );
        }

        // Executa limpeza de até 30 imagens por requisição AJAX
        $batch_size = 30;
        $batch = array_slice( $ids, 0, $batch_size );
        $remaining = array_slice( $ids, $batch_size );

        $stats = $this->cleanup->delete_attachments_batch( $batch );

        $completed = empty( $remaining );

        if ( $completed ) {
            // Se concluído, registra no histórico
            $history = get_option( 'isd_cleanup_history', array() );
            $history[] = array(
                'timestamp'   => time(),
                'type'        => 'manual',
                'count'       => (int) $_POST['accumulated_count'] + $stats['count'],
                'bytes_saved' => (int) $_POST['accumulated_bytes'] + $stats['bytes_saved'],
            );

            if ( count( $history ) > 20 ) {
                $history = array_slice( $history, -20 );
            }

            update_option( 'isd_cleanup_history', $history );
        }

        wp_send_json_success( array(
            'completed'         => $completed,
            'deleted_count'     => $stats['count'],
            'bytes_saved'       => $stats['bytes_saved'],
            'remaining_ids'     => $remaining,
        ) );
    }
}
