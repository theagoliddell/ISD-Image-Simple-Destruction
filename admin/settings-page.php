<?php
/**
 * Template para a página de configurações administrativa
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap');

    #isd-admin-wrap {
        font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        margin: 20px 20px 20px 0;
        max-width: 1200px;
        color: #1f2937;
    }

    /* Cabeçalho */
    .isd-header {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #ffffff;
        padding: 35px 30px;
        border-radius: 16px;
        margin-bottom: 30px;
        box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.3);
    }
    .isd-header h1 {
        color: #ffffff;
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 8px 0;
        letter-spacing: -0.025em;
        line-height: 1.2;
    }
    .isd-header p {
        font-size: 16px;
        margin: 0;
        opacity: 0.9;
        font-weight: 300;
    }

    /* Grid do Dashboard */
    .isd-dashboard-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }
    @media (min-width: 900px) {
        .isd-dashboard-grid {
            grid-template-columns: 7fr 5fr;
        }
    }

    /* Cards */
    .isd-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 16px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03), 0 2px 4px -1px rgba(0, 0, 0, 0.02);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .isd-card:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.03);
    }
    .isd-card h2 {
        font-size: 18px;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #111827;
    }

    /* Alertas */
    .isd-alert {
        padding: 15px 20px;
        border-radius: 8px;
        margin-bottom: 25px;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
    }
    .isd-alert-success {
        background-color: #ecfdf5;
        color: #065f46;
        border-left: 4px solid #10b981;
    }

    /* Formulário */
    .isd-form-group {
        margin-bottom: 24px;
    }
    .isd-form-group label.isd-label {
        display: block;
        font-weight: 500;
        margin-bottom: 8px;
        color: #374151;
        font-size: 14px;
    }
    .isd-input-group {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    .isd-input {
        padding: 10px 14px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 14px;
        color: #1f2937;
        background-color: #f9fafb;
        font-family: inherit;
        transition: all 0.2s;
    }
    .isd-input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        background-color: #ffffff;
    }
    .isd-input[type="number"] {
        width: 80px;
    }
    .isd-description {
        margin-top: 6px;
        font-size: 12px;
        color: #6b7280;
    }

    /* Seletor de Categorias */
    .isd-category-selector {
        background-color: #f9fafb;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 12px 16px;
        max-height: 150px;
        overflow-y: auto;
        display: grid;
        grid-template-columns: 1fr;
        gap: 8px;
        margin-top: 5px;
    }
    @media (min-width: 600px) {
        .isd-category-selector {
            grid-template-columns: 1fr 1fr;
        }
    }
    .isd-category-checkbox-label {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13px;
        color: #374151;
        cursor: pointer;
    }
    .isd-category-checkbox-label input[type="checkbox"] {
        border-radius: 4px;
        border: 1px solid #d1d5db;
        cursor: pointer;
    }

    /* Switch iOS Estilo */
    .isd-switch-container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px;
        background-color: #f9fafb;
        border-radius: 8px;
        border: 1px solid #f3f4f6;
        margin-bottom: 12px;
    }
    .isd-switch-info {
        flex: 1;
        padding-right: 15px;
    }
    .isd-switch-title {
        font-weight: 500;
        font-size: 14px;
        color: #374151;
        margin-bottom: 2px;
    }
    .isd-switch-desc {
        font-size: 12px;
        color: #6b7280;
    }
    .isd-switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 26px;
        flex-shrink: 0;
    }
    .isd-switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .isd-slider {
        position: absolute;
        cursor: pointer;
        top: 0; left: 0; right: 0; bottom: 0;
        background-color: #e5e7eb;
        transition: .3s;
        border-radius: 34px;
    }
    .isd-slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
        box-shadow: 0 1px 3px rgba(0,0,0,0.15);
    }
    input:checked + .isd-slider {
        background-color: #4f46e5;
    }
    input:checked + .isd-slider:before {
        transform: translateX(24px);
    }

    /* Botões */
    .isd-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        border: none;
        transition: all 0.2s;
        font-family: inherit;
        text-decoration: none;
    }
    .isd-btn-primary {
        background: linear-gradient(135deg, #4f46e5 0%, #6366f1 100%);
        color: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2);
    }
    .isd-btn-primary:hover {
        background: linear-gradient(135deg, #4338ca 0%, #4f46e5 100%);
        box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3);
    }
    .isd-btn-secondary {
        background-color: #ffffff;
        color: #4b5563;
        border: 1px solid #d1d5db;
    }
    .isd-btn-secondary:hover {
        background-color: #f9fafb;
        color: #1f2937;
    }
    .isd-btn-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
    }
    .isd-btn-danger:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
        box-shadow: 0 10px 15px -3px rgba(239, 68, 68, 0.3);
    }
    .isd-btn:disabled {
        background: #e5e7eb !important;
        color: #9ca3af !important;
        cursor: not-allowed;
        box-shadow: none !important;
    }

    /* Barra de Progresso */
    #isd-progress-wrapper {
        background-color: #f9fafb;
        border-radius: 12px;
        padding: 20px;
        margin-top: 20px;
        border: 1px solid #e5e7eb;
        display: none;
    }
    .isd-progress-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }
    #isd-progress-text {
        font-size: 14px;
        font-weight: 500;
        color: #374151;
    }
    .isd-progress-status-container {
        display: flex;
        align-items: center;
    }
    .isd-spinner {
        display: inline-block;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(79, 70, 229, 0.1);
        border-radius: 50%;
        border-top-color: #4f46e5;
        animation: spin 1s ease-in-out infinite;
        margin-right: 8px;
    }
    .isd-progress-status-container.running .isd-spinner { display: inline-block; }
    .isd-progress-status-container.completed .isd-spinner { display: none; }
    .isd-progress-status-container.error .isd-spinner { display: none; }
    
    .isd-status-icon {
        display: none;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        margin-right: 8px;
    }
    .isd-progress-status-container.completed .isd-status-icon {
        display: inline-block;
        background-color: #10b981;
    }
    .isd-progress-status-container.error .isd-status-icon {
        display: inline-block;
        background-color: #ef4444;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .isd-progress-bar-container {
        background-color: #e5e7eb;
        border-radius: 9999px;
        height: 10px;
        overflow: hidden;
        margin: 12px 0;
        position: relative;
    }
    #isd-progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #4f46e5 0%, #a855f7 100%);
        width: 0%;
        transition: width 0.3s ease;
        border-radius: 9999px;
        position: relative;
        overflow: hidden;
    }
    #isd-progress-bar-fill::after {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: linear-gradient(
            90deg,
            rgba(255,255,255,0) 0%,
            rgba(255,255,255,0.4) 50%,
            rgba(255,255,255,0) 100%
        );
        animation: progress-shimmer 1.5s infinite;
    }
    @keyframes progress-shimmer {
        0% { transform: translateX(-100%); }
        100% { transform: translateX(100%); }
    }
    #isd-progress-details {
        font-size: 13px;
        color: #4b5563;
        line-height: 1.5;
        margin-top: 10px;
    }

    /* Tabela Status e Histórico */
    .isd-status-list {
        margin: 0 0 20px 0;
        padding: 0;
        list-style: none;
    }
    .isd-status-list li {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #f3f4f6;
        font-size: 14px;
    }
    .isd-status-list li:last-child {
        border-bottom: none;
    }
    .isd-status-label {
        color: #4b5563;
        font-weight: 500;
    }
    .isd-status-value {
        color: #111827;
        font-weight: 600;
    }

    .isd-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .isd-table th {
        background-color: #f9fafb;
        font-weight: 600;
        color: #374151;
        padding: 10px 12px;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }
    .isd-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #f3f4f6;
        color: #4b5563;
    }
    .isd-table tr:last-child td {
        border-bottom: none;
    }

    .isd-badge {
        display: inline-block;
        padding: 2px 8px;
        font-size: 10px;
        font-weight: 600;
        border-radius: 9999px;
        text-transform: uppercase;
    }
    .isd-badge-auto {
        background-color: #dbeafe;
        color: #1e40af;
    }
    .isd-badge-manual {
        background-color: #f3e8ff;
        color: #6b21a8;
    }

    .isd-empty {
        padding: 30px;
        text-align: center;
        color: #9ca3af;
        font-style: italic;
    }
</style>

<div id="isd-admin-wrap">
    
    <!-- Cabeçalho -->
    <div class="isd-header">
        <h1>ISD - Image Simple Destruction</h1>
        <p><?php esc_html_e( 'Remove media files from old or broken posts to keep your server light and optimized.', 'isd-image-simple-destruction' ); ?></p>
    </div>

    <!-- Feedback de atualização -->
    <?php
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended
    if ( isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'] ) :
    ?>
        <div class="isd-alert isd-alert-success">
            <?php esc_html_e( 'Settings saved and applied successfully!', 'isd-image-simple-destruction' ); ?>
        </div>
    <?php endif; ?>

    <div class="isd-dashboard-grid">
        
        <!-- Coluna da Esquerda: Configurações e Ação -->
        <div class="isd-column">
            
            <form method="post" action="">
                <?php wp_nonce_field( 'isd_save_settings_action', 'isd_settings_nonce' ); ?>
                
                <div class="isd-card">
                    <h2><?php esc_html_e( 'Automatic Cleanup Settings', 'isd-image-simple-destruction' ); ?></h2>
                    
                    <!-- Toggle Geral Habilitado -->
                    <div class="isd-switch-container">
                        <div class="isd-switch-info">
                            <div class="isd-switch-title"><?php esc_html_e( 'Enable Automatic Cleanup', 'isd-image-simple-destruction' ); ?></div>
                            <div class="isd-switch-desc"><?php esc_html_e( 'Activates daily/periodic automatic cleanup via WP Cron.', 'isd-image-simple-destruction' ); ?></div>
                        </div>
                        <label class="isd-switch">
                            <input type="checkbox" name="enabled" value="1" <?php checked( 1, $settings['enabled'] ); ?>>
                            <span class="isd-slider"></span>
                        </label>
                    </div>
                    
                    <!-- Intervalo Cron -->
                    <div class="isd-form-group">
                        <label class="isd-label" for="cron_interval"><?php esc_html_e( 'Cleanup Frequency', 'isd-image-simple-destruction' ); ?></label>
                        <select name="cron_interval" id="cron_interval" class="isd-input">
                            <option value="daily" <?php selected( 'daily', $settings['cron_interval'] ); ?>><?php esc_html_e( 'Daily', 'isd-image-simple-destruction' ); ?></option>
                            <option value="twicedaily" <?php selected( 'twicedaily', $settings['cron_interval'] ); ?>><?php esc_html_e( 'Twice a day', 'isd-image-simple-destruction' ); ?></option>
                            <option value="weekly" <?php selected( 'weekly', $settings['cron_interval'] ); ?>><?php esc_html_e( 'Weekly', 'isd-image-simple-destruction' ); ?></option>
                        </select>
                        <p class="isd-description"><?php esc_html_e( 'Select how often the server will perform the automatic scan.', 'isd-image-simple-destruction' ); ?></p>
                    </div>

                    <!-- Tempo limite para Posts Antigos -->
                    <div class="isd-form-group">
                        <label class="isd-label"><?php esc_html_e( 'Delete images from posts published more than:', 'isd-image-simple-destruction' ); ?></label>
                        <div class="isd-input-group">
                            <input type="number" min="1" name="threshold_value" value="<?php echo esc_attr( $settings['threshold_value'] ); ?>" class="isd-input">
                            <select name="threshold_unit" class="isd-input">
                                <option value="days" <?php selected( 'days', $settings['threshold_unit'] ); ?>><?php esc_html_e( 'Day(s)', 'isd-image-simple-destruction' ); ?></option>
                                <option value="months" <?php selected( 'months', $settings['threshold_unit'] ); ?>><?php esc_html_e( 'Month(s)', 'isd-image-simple-destruction' ); ?></option>
                            </select>
                        </div>
                        <p class="isd-description"><?php esc_html_e( 'Images from posts published before this limit will be physically removed. The post text remains unchanged.', 'isd-image-simple-destruction' ); ?></p>
                    </div>

                    <!-- Categorias a Excluir -->
                    <div class="isd-form-group">
                        <label class="isd-label"><?php esc_html_e( 'Exclude Categories from Cleanup:', 'isd-image-simple-destruction' ); ?></label>
                        <div class="isd-category-selector">
                            <?php 
                            $categories = get_categories( array( 'hide_empty' => 0 ) );
                            $exclude_categories = isset( $settings['exclude_categories'] ) ? $settings['exclude_categories'] : array();
                            if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) :
                                foreach ( $categories as $cat ) :
                                    ?>
                                    <label class="isd-category-checkbox-label">
                                        <input type="checkbox" name="exclude_categories[]" value="<?php echo esc_attr( $cat->term_id ); ?>" <?php checked( in_array( $cat->term_id, $exclude_categories, true ) ); ?>>
                                        <?php echo esc_html( $cat->name ); ?>
                                    </label>
                                    <?php
                                endforeach;
                            else :
                                echo '<p class="isd-description">' . esc_html__( 'No categories found.', 'isd-image-simple-destruction' ) . '</p>';
                            endif;
                            ?>
                        </div>
                        <p class="isd-description"><?php esc_html_e( 'Images from posts belonging to the selected categories will NOT be deleted.', 'isd-image-simple-destruction' ); ?></p>
                    </div>
                </div>

                <div class="isd-card">
                    <h2><?php esc_html_e( 'Broken / Orphaned Image Rules', 'isd-image-simple-destruction' ); ?></h2>

                    <!-- Deletar imagens com pai deletado -->
                    <div class="isd-switch-container">
                        <div class="isd-switch-info">
                            <div class="isd-switch-title"><?php esc_html_e( 'Remove if the original post was deleted', 'isd-image-simple-destruction' ); ?></div>
                            <div class="isd-switch-desc"><?php esc_html_e( 'Deletes images whose source post no longer exists in the database.', 'isd-image-simple-destruction' ); ?></div>
                        </div>
                        <label class="isd-switch">
                            <input type="checkbox" name="delete_broken_parent" value="1" <?php checked( 1, $settings['delete_broken_parent'] ); ?>>
                            <span class="isd-slider"></span>
                        </label>
                    </div>

                    <!-- Deletar imagens na Lixeira -->
                    <div class="isd-switch-container">
                        <div class="isd-switch-info">
                            <div class="isd-switch-title"><?php esc_html_e( 'Remove from trashed posts', 'isd-image-simple-destruction' ); ?></div>
                            <div class="isd-switch-desc"><?php esc_html_e( 'Deletes images from posts that are currently in the Trash.', 'isd-image-simple-destruction' ); ?></div>
                        </div>
                        <label class="isd-switch">
                            <input type="checkbox" name="delete_trash_attachments" value="1" <?php checked( 1, $settings['delete_trash_attachments'] ); ?>>
                            <span class="isd-slider"></span>
                        </label>
                    </div>

                    <!-- Deletar imagens órfãs -->
                    <div class="isd-switch-container">
                        <div class="isd-switch-info">
                            <div class="isd-switch-title"><?php esc_html_e( 'Remove unattached (orphaned) images', 'isd-image-simple-destruction' ); ?></div>
                            <div class="isd-switch-desc"><?php esc_html_e( 'Deletes media files that are not linked to any post.', 'isd-image-simple-destruction' ); ?></div>
                        </div>
                        <label class="isd-switch">
                            <input type="checkbox" name="delete_orphaned" value="1" <?php checked( 1, $settings['delete_orphaned'] ); ?>>
                            <span class="isd-slider"></span>
                        </label>
                    </div>

                    <div style="margin-top: 25px;">
                        <input type="submit" name="isd_settings_submit" class="isd-btn isd-btn-primary" value="<?php esc_attr_e( 'Save Settings', 'isd-image-simple-destruction' ); ?>">
                    </div>
                </div>

            </form>

            <div class="isd-card">
                <h2><?php esc_html_e( 'Manual Cleanup', 'isd-image-simple-destruction' ); ?></h2>
                <p><?php esc_html_e( 'Run an instant scan using the rules configured above. The process runs safely in small background batches to avoid server overload.', 'isd-image-simple-destruction' ); ?></p>
                
                <button id="isd-start-cleanup" class="isd-btn isd-btn-danger"><?php esc_html_e( 'Clean Now', 'isd-image-simple-destruction' ); ?></button>

                <!-- Wrapper de Progresso -->
                <div id="isd-progress-wrapper">
                    <div class="isd-progress-header">
                        <span id="isd-progress-text"><?php esc_html_e( 'Starting scan...', 'isd-image-simple-destruction' ); ?></span>
                        <div id="isd-progress-status-icon" class="isd-progress-status-container">
                            <span class="isd-spinner"></span>
                            <span class="isd-status-icon"></span>
                        </div>
                    </div>
                    <div class="isd-progress-bar-container">
                        <div id="isd-progress-bar-fill"></div>
                    </div>
                    <div id="isd-progress-details"></div>
                </div>
            </div>

        </div>

        <!-- Coluna da Direita: Status e Logs -->
        <div class="isd-column">
            
            <div class="isd-card">
                <h2><?php esc_html_e( 'Server Status', 'isd-image-simple-destruction' ); ?></h2>
                <ul class="isd-status-list">
                    <li>
                        <span class="isd-status-label"><?php esc_html_e( 'Automatic Scheduling', 'isd-image-simple-destruction' ); ?></span>
                        <span class="isd-status-value">
                            <?php if ( $settings['enabled'] ) : ?>
                                <span style="color: #10b981;">● <?php esc_html_e( 'Active', 'isd-image-simple-destruction' ); ?></span>
                            <?php else : ?>
                                <span style="color: #ef4444;">● <?php esc_html_e( 'Inactive', 'isd-image-simple-destruction' ); ?></span>
                            <?php endif; ?>
                        </span>
                    </li>
                    <li>
                        <span class="isd-status-label"><?php esc_html_e( 'Cron Frequency', 'isd-image-simple-destruction' ); ?></span>
                        <span class="isd-status-value">
                            <?php
                            switch ( $settings['cron_interval'] ) {
                                case 'daily':
                                    esc_html_e( 'Daily', 'isd-image-simple-destruction' );
                                    break;
                                case 'twicedaily':
                                    esc_html_e( 'Twice a day', 'isd-image-simple-destruction' );
                                    break;
                                case 'weekly':
                                    esc_html_e( 'Weekly', 'isd-image-simple-destruction' );
                                    break;
                            }
                            ?>
                        </span>
                    </li>
                    <li>
                        <span class="isd-status-label"><?php esc_html_e( 'Next Scheduled Run', 'isd-image-simple-destruction' ); ?></span>
                        <span class="isd-status-value">
                            <?php
                            $next_cron = wp_next_scheduled( 'isd_scheduled_cleanup' );
                            if ( $next_cron ) {
                                echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $next_cron ) );
                            } else {
                                esc_html_e( 'No active schedule', 'isd-image-simple-destruction' );
                            }
                            ?>
                        </span>
                    </li>
                    <li>
                        <span class="isd-status-label"><?php esc_html_e( 'Expiration Filter', 'isd-image-simple-destruction' ); ?></span>
                        <span class="isd-status-value">
                            > <?php echo esc_html( $settings['threshold_value'] ); ?> 
                            <?php echo $settings['threshold_unit'] === 'months' ? esc_html__( 'Month(s)', 'isd-image-simple-destruction' ) : esc_html__( 'Day(s)', 'isd-image-simple-destruction' ); ?>
                        </span>
                    </li>
                </ul>
            </div>

            <div class="isd-card">
                <h2><?php esc_html_e( 'Recent Cleanup History', 'isd-image-simple-destruction' ); ?></h2>
                
                <?php if ( empty( $history ) ) : ?>
                    <div class="isd-empty">
                        <?php esc_html_e( 'No cleanups recorded yet.', 'isd-image-simple-destruction' ); ?>
                    </div>
                <?php else : ?>
                    <table class="isd-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e( 'Date/Time', 'isd-image-simple-destruction' ); ?></th>
                                <th><?php esc_html_e( 'Type', 'isd-image-simple-destruction' ); ?></th>
                                <th><?php esc_html_e( 'Deleted', 'isd-image-simple-destruction' ); ?></th>
                                <th><?php esc_html_e( 'Space Saved', 'isd-image-simple-destruction' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            // Exibe os mais recentes primeiro
                            $reversed_history = array_reverse( $history );
                            foreach ( $reversed_history as $log ) : 
                            ?>
                                <tr>
                                    <td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log['timestamp'] ) ); ?></td>
                                    <td>
                                        <?php if ( 'auto' === $log['type'] ) : ?>
                                            <span class="isd-badge isd-badge-auto"><?php esc_html_e( 'Auto', 'isd-image-simple-destruction' ); ?></span>
                                        <?php else : ?>
                                            <span class="isd-badge isd-badge-manual"><?php esc_html_e( 'Manual', 'isd-image-simple-destruction' ); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo (int) $log['count']; ?> <?php esc_html_e( 'images', 'isd-image-simple-destruction' ); ?></td>
                                    <td><strong><?php echo esc_html( size_format( $log['bytes_saved'], 2 ) ); ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

        </div>

    </div>

</div>
