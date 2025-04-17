<?php

class LWR_Upgrade
{
    public $version;
    public $last_version;

    function __construct() {
        $this->version = LWR_VERSION;
        $this->last_version = get_option( 'lwr_version' );

        if ( version_compare( $this->last_version, $this->version, '<' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            $this->upgrade_database();
            update_option( 'lwr_version', $this->version );
        }
    }

    /**
     * Upgrade the database schema using dbDelta()
     */
    private function upgrade_database() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->base_prefix . 'lwr_log';
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT unsigned NOT NULL auto_increment,
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
            x_redirect_by VARCHAR(255),
            date_added DATETIME,
            PRIMARY KEY  (id)
        ) $charset_collate;";
        
        // dbDelta() will compare the current table structure with this SQL statement
        // and add/modify the table as needed
        dbDelta( $sql );
    }
} 