<?php

class LWR_Upgrade
{
    public $version;
    public $last_version;

    function __construct() {
        $this->version = LWR_VERSION;
        $this->last_version = get_option( 'lwr_version' );

        if ( version_compare( $this->last_version, $this->version, '<' ) ) {
            if ( version_compare( $this->last_version, '0.1.0', '<' ) ) {
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
                $this->clean_install();
            }
            else {
                $this->run_upgrade();
            }

            update_option( 'lwr_version', $this->version );
        }
    }


    private function clean_install() {
        global $wpdb;

        $sql = "
        CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}lwr_log (
            id BIGINT unsigned not null auto_increment,
            blog_id BIGINT,
            location TEXT,
            status VARCHAR(10),
            referer TEXT,
            request_uri TEXT,
            user_agent TEXT,
            user_id BIGINT,
            ip_address VARCHAR(45),
            cookies TEXT,
            backtrace LONGTEXT,
            date_added DATETIME,
            PRIMARY KEY (id)
        ) DEFAULT CHARSET=utf8mb4";
        
        $wpdb->query( $sql );
    }


    private function run_upgrade() {
        global $wpdb;
        
        // For future upgrades
    }
} 