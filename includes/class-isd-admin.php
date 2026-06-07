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
            __( 'ISD - Image Simple Destruction', 'isd-image-simple-destruction' ),
            __( 'Image Destruction', 'isd-image-simple-destruction' ),
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
                    'scanning'      => __( 'Scanning media in the database...', 'isd-image-simple-destruction' ),
                    'deleting'      => __( 'Deleting images... {percent}% completed.', 'isd-image-simple-destruction' ),
                    'completed'     => __( 'Cleanup completed successfully!', 'isd-image-simple-destruction' ),
                    'no_images'     => __( 'No images found for the selected criteria.', 'isd-image-simple-destruction' ),
                    'error'         => __( 'An error occurred during the deletion process.', 'isd-image-simple-destruction' ),
                    'confirm'       => __( 'Are you sure you want to permanently delete all matching images? This action cannot be undone.', 'isd-image-simple-destruction' ),
                    'starting'      => __( 'Starting deletion of', 'isd-image-simple-destruction' ),
                    'est_size'      => __( 'Est. size:', 'isd-image-simple-destruction' ),
                    'images'        => __( 'images', 'isd-image-simple-destruction' ),
                    'images_deleted'=> __( 'Images deleted:', 'isd-image-simple-destruction' ),
                    'space_freed'   => __( 'Space freed so far:', 'isd-image-simple-destruction' ),
                    'error_prefix'  => __( 'Error:', 'isd-image-simple-destruction' ),
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
            wp_die( esc_html__( 'Access denied.', 'isd-image-simple-destruction' ) );
        }

        check_admin_referer( 'isd_save_settings_action', 'isd_settings_nonce' );

        $old_settings = $this->cleanup->get_settings();

        // Sanitização dos dados
        $threshold_val = isset( $_POST['threshold_value'] ) ? max( 1, (int) $_POST['threshold_value'] ) : 30;
        $threshold_unit_raw = isset( $_POST['threshold_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['threshold_unit'] ) ) : 'days';
        $threshold_unit = in_array( $threshold_unit_raw, array( 'days', 'months' ), true ) ? $threshold_unit_raw : 'days';
        
        $cron_interval_raw = isset( $_POST['cron_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['cron_interval'] ) ) : 'daily';
        $cron_interval = in_array( $cron_interval_raw, array( 'daily', 'twicedaily', 'weekly' ), true ) ? $cron_interval_raw : 'daily';

        $new_settings = array(
            'enabled'                   => isset( $_POST['enabled'] ) ? 1 : 0,
            'threshold_value'           => $threshold_val,
            'threshold_unit'            => $threshold_unit,
            'delete_orphaned'           => isset( $_POST['delete_orphaned'] ) ? 1 : 0,
            'delete_trash_attachments'  => isset( $_POST['delete_trash_attachments'] ) ? 1 : 0,
            'delete_broken_parent'      => isset( $_POST['delete_broken_parent'] ) ? 1 : 0,
            'cron_interval'             => $cron_interval,
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
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'isd-image-simple-destruction' ) ) );
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
            wp_send_json_error( array( 'message' => __( 'Access denied.', 'isd-image-simple-destruction' ) ) );
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
            $accumulated_count = isset( $_POST['accumulated_count'] ) ? (int) $_POST['accumulated_count'] : 0;
            $accumulated_bytes = isset( $_POST['accumulated_bytes'] ) ? (int) $_POST['accumulated_bytes'] : 0;

            // Se concluído, registra no histórico
            $history = get_option( 'isd_cleanup_history', array() );
            $history[] = array(
                'timestamp'   => time(),
                'type'        => 'manual',
                'count'       => $accumulated_count + $stats['count'],
                'bytes_saved' => $accumulated_bytes + $stats['bytes_saved'],
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
