<?php
/**
 * Plugin Name: ISD - Image Simple Destruction
 * Plugin URI:  https://github.com/theagoliddell/ISD-Image-Simple-Destruction
 * Description: It automatically or manually removes media files (images) from the server that belong to old or broken posts, making the server lighter.
 * Version:     1.0.0
 * Author:      Theago Liddell
 * Author URI:  https://github.com/theagoliddell
 * License:     GPLv2 or later
 * Text Domain: isd-image-destruction
 * Domain Path: /languages
 */

// Evita o acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Definição de Constantes
define( 'ISD_VERSION', '1.0.0' );
define( 'ISD_PATH', plugin_dir_path( __FILE__ ) );
define( 'ISD_URL', plugin_dir_url( __FILE__ ) );
define( 'ISD_BASENAME', plugin_basename( __FILE__ ) );

// Inclui arquivos necessários
require_once ISD_PATH . 'includes/class-isd-cleanup.php';
require_once ISD_PATH . 'includes/class-isd-admin.php';

/**
 * Função de Ativação do Plugin
 */
function isd_activate_plugin() {
    // Define configurações padrão se não existirem
    $default_settings = array(
        'enabled'                   => 0,
        'threshold_value'           => 30,
        'threshold_unit'            => 'days',
        'delete_orphaned'           => 1,
        'delete_trash_attachments'  => 1,
        'delete_broken_parent'      => 1,
        'cron_interval'             => 'daily',
        'exclude_categories'        => array(),
    );

    if ( ! get_option( 'isd_settings' ) ) {
        update_option( 'isd_settings', $default_settings );
    }

    // Agenda o cron se habilitado nas configurações padrões ou existentes
    $settings = get_option( 'isd_settings', $default_settings );
    if ( ! empty( $settings['enabled'] ) ) {
        isd_schedule_cleanup_cron( $settings['cron_interval'] );
    }
}
register_activation_hook( __FILE__, 'isd_activate_plugin' );

/**
 * Função de Desativação do Plugin
 */
function isd_deactivate_plugin() {
    isd_unschedule_cleanup_cron();
}
register_deactivation_hook( __FILE__, 'isd_deactivate_plugin' );

/**
 * Agenda o Cron Job do Plugin
 *
 * @param string $interval Intervalo do cron ('daily', 'twicedaily', 'weekly').
 */
function isd_schedule_cleanup_cron( $interval = 'daily' ) {
    isd_unschedule_cleanup_cron(); // Limpa agendamentos anteriores
    if ( ! wp_next_scheduled( 'isd_scheduled_cleanup' ) ) {
        wp_schedule_event( time(), $interval, 'isd_scheduled_cleanup' );
    }
}

/**
 * Remove o agendamento Cron do Plugin
 */
function isd_unschedule_cleanup_cron() {
    $timestamp = wp_next_scheduled( 'isd_scheduled_cleanup' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'isd_scheduled_cleanup' );
    }
}

/**
 * Inicialização do Plugin
 */
function isd_init() {
    // Inicializa a limpeza (cron hook)
    $cleanup = new ISD_Cleanup();
    add_action( 'isd_scheduled_cleanup', array( $cleanup, 'run_cleanup' ) );

    // Inicializa a interface administrativa
    if ( is_admin() ) {
        new ISD_Admin( $cleanup );
    }
}
add_action( 'plugins_loaded', 'isd_init' );
