<?php
/**
 * Classe responsável pela lógica de limpeza das imagens
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class ISD_Cleanup {

    /**
     * Obtém as configurações atuais do plugin
     */
    public function get_settings() {
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

        return get_option( 'isd_settings', $default_settings );
    }

    /**
     * Retorna a lista de todos os caminhos físicos de arquivos associados a um anexo
     *
     * @param int $attachment_id ID do anexo.
     * @return array Lista de caminhos completos.
     */
    public function get_attachment_files( $attachment_id ) {
        $files = array();
        $main_file = get_attached_file( $attachment_id );

        if ( $main_file && file_exists( $main_file ) ) {
            $files[] = $main_file;
            $dir = dirname( $main_file );
            $meta = wp_get_attachment_metadata( $attachment_id );

            if ( ! empty( $meta['sizes'] ) && is_array( $meta['sizes'] ) ) {
                foreach ( $meta['sizes'] as $size ) {
                    if ( ! empty( $size['file'] ) ) {
                        $size_file = $dir . '/' . $size['file'];
                        if ( file_exists( $size_file ) ) {
                            $files[] = $size_file;
                        }
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Calcula o tamanho total em bytes dos arquivos associados a um anexo
     *
     * @param int $attachment_id ID do anexo.
     * @return int Tamanho em bytes.
     */
    public function get_attachment_size( $attachment_id ) {
        $files = $this->get_attachment_files( $attachment_id );
        $total_size = 0;
        foreach ( $files as $file ) {
            $total_size += filesize( $file );
        }
        return $total_size;
    }

    /**
     * Encontra IDs de anexos de imagens antigas
     *
     * @param int $limit Limite de resultados (0 para sem limite).
     * @return array IDs dos anexos.
     */
    public function get_old_images_ids( $limit = 0 ) {
        global $wpdb;
        $settings = $this->get_settings();
        $days_val = (int) $settings['threshold_value'];
        $unit = ( $settings['threshold_unit'] === 'months' ) ? 'MONTH' : 'DAY';

        $limit_clause = $limit > 0 ? $wpdb->prepare( "LIMIT %d", $limit ) : '';

        // Cláusula de exclusão de categorias
        $exclude_clause = '';
        $exclude_categories = ! empty( $settings['exclude_categories'] ) ? array_map( 'intval', $settings['exclude_categories'] ) : array();
        if ( ! empty( $exclude_categories ) ) {
            $categories_placeholder = implode( ',', $exclude_categories );
            $exclude_clause = "AND p.ID NOT IN (
                SELECT object_id FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = 'category'
                AND tt.term_id IN ($categories_placeholder)
            )";
        }

        $query = $wpdb->prepare(
            "SELECT a.ID FROM {$wpdb->posts} a
             INNER JOIN {$wpdb->posts} p ON a.post_parent = p.ID
             WHERE a.post_type = 'attachment'
             AND a.post_mime_type LIKE 'image/%%'
             AND p.post_type = 'post'
             AND p.post_status = 'publish'
             $exclude_clause
             AND p.post_date < DATE_SUB(NOW(), INTERVAL %d $unit)
             ORDER BY a.ID ASC
             $limit_clause",
            $days_val
        );

        return $wpdb->get_col( $query );
    }

    /**
     * Encontra IDs de anexos de imagens de posts quebrados/deletados
     *
     * @param int $limit Limite de resultados (0 para sem limite).
     * @return array IDs dos anexos.
     */
    public function get_broken_images_ids( $limit = 0 ) {
        global $wpdb;
        $settings = $this->get_settings();
        $ids = array();
        
        $limit_clause = $limit > 0 ? $wpdb->prepare( "LIMIT %d", $limit ) : '';

        // 1. Imagens com post pai que foi deletado
        if ( ! empty( $settings['delete_broken_parent'] ) ) {
            $query = "SELECT a.ID FROM {$wpdb->posts} a
                      LEFT JOIN {$wpdb->posts} p ON a.post_parent = p.ID
                      WHERE a.post_type = 'attachment'
                      AND a.post_mime_type LIKE 'image/%%'
                      AND a.post_parent > 0
                      AND p.ID IS NULL
                      ORDER BY a.ID ASC
                      $limit_clause";
            $res = $wpdb->get_col( $query );
            if ( ! empty( $res ) ) {
                $ids = array_merge( $ids, $res );
            }
        }

        // Se já atingiu o limite, retorna
        if ( $limit > 0 && count( $ids ) >= $limit ) {
            return array_slice( $ids, 0, $limit );
        }

        // 2. Imagens associadas a posts na lixeira
        if ( ! empty( $settings['delete_trash_attachments'] ) ) {
            $current_limit = $limit > 0 ? ($limit - count( $ids )) : 0;
            $current_limit_clause = $current_limit > 0 ? $wpdb->prepare( "LIMIT %d", $current_limit ) : '';

            $query = "SELECT a.ID FROM {$wpdb->posts} a
                      INNER JOIN {$wpdb->posts} p ON a.post_parent = p.ID
                      WHERE a.post_type = 'attachment'
                      AND a.post_mime_type LIKE 'image/%%'
                      AND p.post_status = 'trash'
                      ORDER BY a.ID ASC
                      $current_limit_clause";
            $res = $wpdb->get_col( $query );
            if ( ! empty( $res ) ) {
                $ids = array_merge( $ids, $res );
            }
        }

        // Se já atingiu o limite, retorna
        if ( $limit > 0 && count( $ids ) >= $limit ) {
            return array_slice( $ids, 0, $limit );
        }

        // 3. Imagens órfãs (sem post_parent)
        if ( ! empty( $settings['delete_orphaned'] ) ) {
            $current_limit = $limit > 0 ? ($limit - count( $ids )) : 0;
            $current_limit_clause = $current_limit > 0 ? $wpdb->prepare( "LIMIT %d", $current_limit ) : '';

            $query = "SELECT ID FROM {$wpdb->posts}
                      WHERE post_type = 'attachment'
                      AND post_mime_type LIKE 'image/%%'
                      AND post_parent = 0
                      ORDER BY ID ASC
                      $current_limit_clause";
            $res = $wpdb->get_col( $query );
            if ( ! empty( $res ) ) {
                $ids = array_merge( $ids, $res );
            }
        }

        if ( $limit > 0 ) {
            return array_slice( array_unique( $ids ), 0, $limit );
        }

        return array_unique( $ids );
    }

    /**
     * Executa a deleção em lote (batch) de imagens
     *
     * @param array $attachment_ids Array de IDs a deletar.
     * @return array Estatísticas da deleção (count e bytes_saved).
     */
    public function delete_attachments_batch( $attachment_ids ) {
        $count = 0;
        $bytes_saved = 0;

        foreach ( $attachment_ids as $id ) {
            $id = (int) $id;
            // Calcula o tamanho antes de deletar
            $size = $this->get_attachment_size( $id );
            
            // Deleta o anexo permanentemente (segundo parâmetro true força deleção do arquivo físico)
            if ( wp_delete_attachment( $id, true ) ) {
                $count++;
                $bytes_saved += $size;
            }
        }

        return array(
            'count'       => $count,
            'bytes_saved' => $bytes_saved,
        );
    }

    /**
     * Limpeza executada pelo WP Cron de forma automática
     */
    public function run_cleanup() {
        // Limita a 100 imagens por execução do cron para evitar gargalos
        $limit = 100;
        
        $old_ids = $this->get_old_images_ids( $limit );
        $broken_ids = array();
        
        if ( count( $old_ids ) < $limit ) {
            $broken_limit = $limit - count( $old_ids );
            $broken_ids = $this->get_broken_images_ids( $broken_limit );
        }

        $all_ids = array_unique( array_merge( $old_ids, $broken_ids ) );

        if ( empty( $all_ids ) ) {
            return;
        }

        $stats = $this->delete_attachments_batch( $all_ids );

        // Registra log da limpeza automática
        $history = get_option( 'isd_cleanup_history', array() );
        $history[] = array(
            'timestamp'   => time(),
            'type'        => 'auto',
            'count'       => $stats['count'],
            'bytes_saved' => $stats['bytes_saved'],
        );

        // Mantém apenas os últimos 20 registros
        if ( count( $history ) > 20 ) {
            $history = array_slice( $history, -20 );
        }

        update_option( 'isd_cleanup_history', $history );
    }
}
