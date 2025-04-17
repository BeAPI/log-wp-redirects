<?php

class LWR_Query
{
    public $wpdb;
    public $sql;
    public $pager_args;


    function __construct() {
        $this->wpdb = $GLOBALS['wpdb'];
    }


    function get_results( $args ) {
        $defaults = [
            'page'          => 1,
            'per_page'      => 50,
            'orderby'       => 'date_added',
            'order'         => 'DESC',
            'search'        => '',
            'blog_id'       => 0, // 0 = all blogs, otherwise specific blog ID
        ];

        $args = array_merge( $defaults, $args );

        $output = [];
        $orderby = in_array( $args['orderby'], [ 'location', 'status', 'date_added', 'blog_id' ] ) ? $args['orderby'] : 'date_added';
        $order = in_array( $args['order'], [ 'ASC', 'DESC' ] ) ? $args['order'] : 'DESC';
        $page = (int) $args['page'];
        $per_page = (int) $args['per_page'];
        $limit = ( ( $page - 1 ) * $per_page ) . ',' . $per_page;
        $where = '';
        
        // Filter by blog ID if specified
        if (!empty($args['blog_id'])) {
            $blog_id = intval($args['blog_id']);
            $where = "WHERE blog_id = {$blog_id}";
        }

        $this->sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                id, blog_id, location, status, referer, request_uri, user_agent, user_id, ip_address, cookies, backtrace, date_added
            FROM {$this->wpdb->base_prefix}lwr_log
            {$where}
            ORDER BY $orderby $order, id DESC
            LIMIT $limit
        ";
        $results = $this->wpdb->get_results( $this->sql, ARRAY_A );

        $total_rows = (int) $this->wpdb->get_var( "SELECT FOUND_ROWS()" );
        $total_pages = ceil( $total_rows / $per_page );

        $this->pager_args = [
            'page'          => $page,
            'per_page'      => $per_page,
            'total_rows'    => $total_rows,
            'total_pages'   => $total_pages,
        ];

        foreach ( $results as $row ) {
            // Format data for display
            $row['status'] = intval($row['status']);
            $row['blog_id'] = intval($row['blog_id']);
            $row['date_raw'] = $row['date_added'];
            $row['date_added'] = LWR()->time_since( $row['date_added'] );
            $row['location'] = esc_url( $row['location'] );
            $row['user'] = $row['user_id'] ? get_userdata($row['user_id']) : null;
            $row['username'] = $row['user'] ? $row['user']->user_login : 'Non-logged user';
            
            // Get blog name for multisite
            $row['blog_name'] = $this->get_blog_name($row['blog_id']);
            
            $output[] = $row;
        }

        return $output;
    }
    
    
    /**
     * Get results for network admin, with better support for filtering by blog
     */
    function get_network_results( $args ) {
        $defaults = [
            'page'          => 1,
            'per_page'      => 50,
            'orderby'       => 'date_added',
            'order'         => 'DESC',
            'search'        => '',
            'blog_id'       => 0, // 0 = all blogs, otherwise specific blog ID
        ];

        $args = array_merge( $defaults, $args );

        $output = [];
        $orderby = in_array( $args['orderby'], [ 'location', 'status', 'date_added', 'blog_id' ] ) ? $args['orderby'] : 'date_added';
        $order = in_array( $args['order'], [ 'ASC', 'DESC' ] ) ? $args['order'] : 'DESC';
        $page = (int) $args['page'];
        $per_page = (int) $args['per_page'];
        $limit = ( ( $page - 1 ) * $per_page ) . ',' . $per_page;
        $where = '';
        
        // Filter by blog ID if specified
        if (!empty($args['blog_id'])) {
            $blog_id = intval($args['blog_id']);
            $where = "WHERE blog_id = {$blog_id}";
        }

        $this->sql = "
            SELECT
                SQL_CALC_FOUND_ROWS
                id, blog_id, location, status, referer, request_uri, user_agent, user_id, ip_address, cookies, backtrace, date_added
            FROM {$this->wpdb->base_prefix}lwr_log
            {$where}
            ORDER BY $orderby $order, id DESC
            LIMIT $limit
        ";
        $results = $this->wpdb->get_results( $this->sql, ARRAY_A );

        $total_rows = (int) $this->wpdb->get_var( "SELECT FOUND_ROWS()" );
        $total_pages = ceil( $total_rows / $per_page );

        $this->pager_args = [
            'page'          => $page,
            'per_page'      => $per_page,
            'total_rows'    => $total_rows,
            'total_pages'   => $total_pages,
        ];

        foreach ( $results as $row ) {
            // Format data for display
            $row['status'] = intval($row['status']);
            $row['blog_id'] = intval($row['blog_id']);
            $row['date_raw'] = $row['date_added'];
            $row['date_added'] = LWR()->time_since( $row['date_added'] );
            $row['location'] = esc_url( $row['location'] );
            
            // Switch to the blog to get user data
            if (function_exists('switch_to_blog')) {
                switch_to_blog($row['blog_id']);
                $row['user'] = $row['user_id'] ? get_userdata($row['user_id']) : null;
                $row['username'] = $row['user'] ? $row['user']->user_login : 'Non-logged user';
                restore_current_blog();
            } else {
                $row['user'] = $row['user_id'] ? get_userdata($row['user_id']) : null;
                $row['username'] = $row['user'] ? $row['user']->user_login : 'Non-logged user';
            }
            
            // Get blog name for multisite
            $row['blog_name'] = $this->get_blog_name($row['blog_id']);
            
            $output[] = $row;
        }

        return $output;
    }
    
    
    /**
     * Get the number of redirects per blog
     */
    function get_redirects_count_per_blog() {
        $results = $this->wpdb->get_results("
            SELECT blog_id, COUNT(*) as count
            FROM {$this->wpdb->base_prefix}lwr_log
            GROUP BY blog_id
            ORDER BY count DESC
        ");
        
        $output = [];
        
        foreach ($results as $row) {
            $blog_id = intval($row->blog_id);
            $count = intval($row->count);
            $blog_name = $this->get_blog_name($blog_id);
            
            $output[] = [
                'blog_id' => $blog_id,
                'count' => $count,
                'blog_name' => $blog_name
            ];
        }
        
        return $output;
    }
    
    
    /**
     * Get the blog name for a given blog ID
     */
    function get_blog_name($blog_id) {
        if (function_exists('is_multisite') && is_multisite() && function_exists('get_blog_details')) {
            $blog_details = get_blog_details($blog_id);
            if ($blog_details) {
                return $blog_details->blogname;
            }
        }
        
        return 'Main Site';
    }
    
    
    /**
     * Get all available blogs for the filter dropdown
     */
    function get_available_blogs() {
        if (!function_exists('is_multisite') || !is_multisite()) {
            return [];
        }
        
        $blogs = [];
        
        if (function_exists('get_sites')) {
            $sites = get_sites();
            foreach ($sites as $site) {
                $blog_id = $site->blog_id;
                $blog_details = get_blog_details($blog_id);
                if ($blog_details) {
                    $blogs[$blog_id] = $blog_details->blogname;
                }
            }
        }
        
        return $blogs;
    }


    function truncate_table() {
        $blog_id = $this->get_current_blog_id();
        $this->wpdb->query( $this->wpdb->prepare(
            "DELETE FROM {$this->wpdb->base_prefix}lwr_log WHERE blog_id = %d",
            $blog_id
        ));
    }
    
    
    /**
     * Truncate network table optionally filtered by blog ID
     */
    function truncate_network_table($blog_id = 0) {
        if ($blog_id > 0) {
            $this->wpdb->query( $this->wpdb->prepare(
                "DELETE FROM {$this->wpdb->base_prefix}lwr_log WHERE blog_id = %d",
                $blog_id
            ));
        } else {
            $this->wpdb->query( "TRUNCATE TABLE {$this->wpdb->base_prefix}lwr_log" );
        }
    }
    
    
    /**
     * Get current blog ID for multisite
     */
    function get_current_blog_id() {
        if (function_exists('is_multisite') && is_multisite()) {
            return get_current_blog_id();
        }
        
        return 1; // Default blog ID for non-multisite
    }


    function paginate() {
        $params = $this->pager_args;

        $output = '';
        $page = (int) $params['page'];
        $per_page = (int) $params['per_page'];
        $total_rows = (int) $params['total_rows'];
        $total_pages = (int) $params['total_pages'];

        // Only show pagination when > 1 page
        if ( 1 < $total_pages ) {

            if ( 3 < $page ) {
                $output .= '<a class="lwr-page first-page" data-page="1">&lt;&lt;</a>';
            }
            if ( 1 < ( $page - 10 ) ) {
                $output .= '<a class="lwr-page" data-page="' . ($page - 10) . '">' . ($page - 10) . '</a>';
            }
            for ( $i = 2; $i > 0; $i-- ) {
                if ( 0 < ( $page - $i ) ) {
                    $output .= '<a class="lwr-page" data-page="' . ($page - $i) . '">' . ($page - $i) . '</a>';
                }
            }

            // Current page
            $output .= '<a class="lwr-page active" data-page="' . $page . '">' . $page . '</a>';

            for ( $i = 1; $i <= 2; $i++ ) {
                if ( $total_pages >= ( $page + $i ) ) {
                    $output .= '<a class="lwr-page" data-page="' . ($page + $i) . '">' . ($page + $i) . '</a>';
                }
            }
            if ( $total_pages > ( $page + 10 ) ) {
                $output .= '<a class="lwr-page" data-page="' . ($page + 10) . '">' . ($page + 10) . '</a>';
            }
            if ( $total_pages > ( $page + 2 ) ) {
                $output .= '<a class="lwr-page last-page" data-page="' . $total_pages . '">&gt;&gt;</a>';
            }
        }

        return $output;
    }
} 