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
     * Retorna IDs de anexos protegidos que NUNCA devem ser deletados (ex: favicon, logo do site, imagens de cabeçalho)
     *
     * @return array IDs dos anexos.
     */
    public function get_protected_attachment_ids() {
        $protected = array();

        // 1. Favicon do Site (Site Icon)
        $site_icon = (int) get_option( 'site_icon' );
        if ( $site_icon ) {
            $protected[] = $site_icon;
        }

        // 2. Custom Logo do WordPress
        $custom_logo = (int) get_theme_mod( 'custom_logo' );
        if ( $custom_logo ) {
            $protected[] = $custom_logo;
        }

        // 3. Imagem de cabeçalho (Header Image)
        $header_image_id = (int) get_theme_mod( 'header_image_data' );
        if ( $header_image_id ) {
            $protected[] = $header_image_id;
        }

        // 4. Imagem de fundo (Background Image)
        $background_image = get_theme_mod( 'background_image' );
        if ( $background_image ) {
            $bg_id = attachment_url_to_postid( $background_image );
            if ( $bg_id ) {
                $protected[] = $bg_id;
            }
        }

        // 5. Varre todos os theme_mods em busca de chaves que possam conter IDs ou URLs de logos/ícones
        $theme_mods = get_theme_mods();
        if ( is_array( $theme_mods ) ) {
            foreach ( $theme_mods as $key => $val ) {
                $key_lower = strtolower( $key );
                if ( strpos( $key_lower, 'logo' ) !== false || strpos( $key_lower, 'icon' ) !== false || strpos( $key_lower, 'favicon' ) !== false ) {
                    if ( is_numeric( $val ) && $val > 0 ) {
                        $protected[] = (int) $val;
                    } elseif ( is_string( $val ) && filter_var( $val, FILTER_VALIDATE_URL ) ) {
                        $att_id = attachment_url_to_postid( $val );
                        if ( $att_id ) {
                            $protected[] = $att_id;
                        }
                    }
                }
            }
        }

        // 6. Varre a tabela wp_options para chaves contendo 'logo', 'icon', 'favicon' ou 'site_icon'
        global $wpdb;
        $options = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_value FROM {$wpdb->options} 
                 WHERE option_name LIKE %s 
                 OR option_name LIKE %s 
                 OR option_name LIKE %s",
                '%logo%',
                '%icon%',
                '%favicon%'
            )
        );
        if ( ! empty( $options ) ) {
            foreach ( $options as $val ) {
                if ( is_numeric( $val ) && $val > 0 ) {
                    $protected[] = (int) $val;
                } elseif ( is_string( $val ) && filter_var( $val, FILTER_VALIDATE_URL ) ) {
                    $att_id = attachment_url_to_postid( $val );
                    if ( $att_id ) {
                        $protected[] = $att_id;
                    }
                } elseif ( is_string( $val ) && is_serialized( $val ) ) {
                    $unserialized = @maybe_unserialize( $val );
                    if ( is_array( $unserialized ) ) {
                        array_walk_recursive( $unserialized, function( $item ) use ( &$protected ) {
                            if ( is_numeric( $item ) && $item > 0 ) {
                                $protected[] = (int) $item;
                            } elseif ( is_string( $item ) && filter_var( $item, FILTER_VALIDATE_URL ) ) {
                                $att_id = attachment_url_to_postid( $item );
                                if ( $att_id ) {
                                    $protected[] = $att_id;
                                }
                            }
                        } );
                    }
                }
            }
        }

        return array_unique( array_filter( $protected ) );
    }

    /**
     * Encontra IDs de anexos de imagens antigas
     *
     * @param int $limit Limite de resultados (0 para sem limite).
     * @return array IDs dos anexos.
     */
    public function get_old_images_ids( $limit = 0 ) {
        $settings = $this->get_settings();
        $days_val = (int) $settings['threshold_value'];

        $parent_ids = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'date_query'     => array(
                array(
                    'column' => 'post_date',
                    'before' => ( $settings['threshold_unit'] === 'months' )
                        ? "-{$days_val} months"
                        : "-{$days_val} days",
                ),
            ),
            'category__not_in' => ! empty( $settings['exclude_categories'] )
                ? array_map( 'intval', $settings['exclude_categories'] )
                : array(),
        ) );

        if ( empty( $parent_ids ) ) {
            return array();
        }

        $attachment_ids = get_posts( array(
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_mime_type' => 'image',
            'posts_per_page' => $limit > 0 ? $limit : -1,
            'fields'         => 'ids',
            'post_parent__in' => $parent_ids,
            'order'          => 'ASC',
            'orderby'        => 'ID',
        ) );

        $protected_ids = $this->get_protected_attachment_ids();
        if ( ! empty( $protected_ids ) ) {
            return array_values( array_diff( $attachment_ids, $protected_ids ) );
        }

        return $attachment_ids;
    }

    /**
     * Encontra IDs de anexos de imagens de posts quebrados/deletados
     *
     * @param int $limit Limite de resultados (0 para sem limite).
     * @return array IDs dos anexos.
     */
    public function get_broken_images_ids( $limit = 0 ) {
        $settings = $this->get_settings();
        $ids = array();
        $protected_ids = $this->get_protected_attachment_ids();

        // 1. Imagens com post pai que foi deletado
        if ( ! empty( $settings['delete_broken_parent'] ) ) {
            $all_with_parent = get_posts( array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            ) );

            foreach ( $all_with_parent as $att_id ) {
                $parent_id = (int) get_post_field( 'post_parent', $att_id );
                if ( $parent_id > 0 && ! get_post( $parent_id ) ) {
                    if ( empty( $protected_ids ) || ! in_array( $att_id, $protected_ids, true ) ) {
                        $ids[] = $att_id;
                        if ( $limit > 0 && count( $ids ) >= $limit ) {
                            break;
                        }
                    }
                }
            }
        }

        if ( $limit > 0 && count( $ids ) >= $limit ) {
            return array_slice( $ids, 0, $limit );
        }

        // 2. Imagens associadas a posts na lixeira
        if ( ! empty( $settings['delete_trash_attachments'] ) ) {
            $current_limit = $limit > 0 ? ( $limit - count( $ids ) ) : 0;

            $trashed_parents = get_posts( array(
                'post_status'    => 'trash',
                'posts_per_page' => -1,
                'fields'         => 'ids',
                'post_type'      => 'any',
            ) );

            if ( ! empty( $trashed_parents ) ) {
                $trash_ids = get_posts( array(
                    'post_type'      => 'attachment',
                    'post_status'    => 'inherit',
                    'post_mime_type' => 'image',
                    'posts_per_page' => $current_limit > 0 ? $current_limit : -1,
                    'fields'         => 'ids',
                    'post_parent__in' => $trashed_parents,
                    'order'          => 'ASC',
                    'orderby'        => 'ID',
                ) );

                if ( ! empty( $trash_ids ) ) {
                    foreach ( $trash_ids as $tid ) {
                        if ( empty( $protected_ids ) || ! in_array( $tid, $protected_ids, true ) ) {
                            $ids[] = $tid;
                        }
                    }
                }
            }
        }

        if ( $limit > 0 && count( $ids ) >= $limit ) {
            return array_slice( $ids, 0, $limit );
        }

        // 3. Imagens órfãs (sem post_parent)
        if ( ! empty( $settings['delete_orphaned'] ) ) {
            $current_limit = $limit > 0 ? ( $limit - count( $ids ) ) : 0;

            $orphaned_ids = get_posts( array(
                'post_type'      => 'attachment',
                'post_status'    => 'inherit',
                'post_mime_type' => 'image',
                'posts_per_page' => $current_limit > 0 ? $current_limit : -1,
                'fields'         => 'ids',
                'post_parent'    => 0,
                'order'          => 'ASC',
                'orderby'        => 'ID',
            ) );

            if ( ! empty( $orphaned_ids ) ) {
                foreach ( $orphaned_ids as $oid ) {
                    if ( empty( $protected_ids ) || ! in_array( $oid, $protected_ids, true ) ) {
                        $ids[] = $oid;
                    }
                }
            }
        }

        if ( $limit > 0 ) {
            return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
        }

        return array_values( array_unique( $ids ) );
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
