<script>
var LWR = {
    response: [],
    query_args: {
        'orderby': 'id',
        'order': 'DESC',
        'page': 1,
        'blog_id': <?php echo get_current_blog_id(); ?>
    },
    nonce: '<?php echo wp_create_nonce( 'lwr_nonce' ); ?>'
};
</script>

<div class="wrap">
    <h2>Log WP Redirects</h2>

    <div class="lwr-actions">
        <div class="lwr-buttons">
            <button class="button lwr-clear" onclick="LWR.clear()">Clear log</button>
            <button class="button lwr-refresh" onclick="LWR.refresh()">Refresh</button>
        </div>
    </div>
    
    <div class="lwr-pager"></div>
    <table class="widefat lwr-listing">
        <thead>
            <tr>
                <td>Redirect URL</td>
                <td title="HTTP status code">Status</td>
                <td>From URL</td>
                <td>User</td>
                <td>Date Added</td>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <div class="lwr-pager"></div>
</div>

<!-- Modal window -->

<div class="media-modal">
    <button class="button-link media-modal-close prev"><span class="media-modal-icon"></span></button>
    <button class="button-link media-modal-close next"><span class="media-modal-icon"></span></button>
    <button class="button-link media-modal-close"><span class="media-modal-icon"></span></button>
    <div class="media-modal-content">
        <div class="media-frame">
            <div class="media-frame-title">
                <h1><?php _e( 'Redirect Details', 'lwr' ); ?></h1>
            </div>
            <div class="media-frame-content">
                <div class="modal-content-wrap">
                    <h3>Redirect To</h3>
                    <div>
                        [<span class="redirect-id"></span>]
                        <span class="redirect-location"></span>
                    </div>
                    
                    <h3>Redirect From</h3>
                    <div class="redirect-request-uri"></div>
                    
                    <h3>Referer</h3>
                    <div class="redirect-referer"></div>
                    
                    <div class="wrapper">
                        <div class="box">
                            <h3>User Info</h3>
                            <div class="redirect-user-info"></div>
                        </div>
                        <div class="box">
                            <h3>Connection Info</h3>
                            <div class="redirect-connection-info"></div>
                        </div>
                    </div>
                    
                    <div class="wrapper">
                        <div class="box">
                            <h3>Cookies</h3>
                            <div class="redirect-cookies"></div>
                        </div>
                        <div class="box">
                            <h3>Redirect Status</h3>
                            <div class="redirect-status"></div>
                        </div>
                    </div>
                    
                    <div class="wrapper">
                        <div class="box">
                            <h3>IP Address</h3>
                            <div class="redirect-ip-address"></div>
                        </div>
                        <div class="box">
                            <h3>Date</h3>
                            <div class="redirect-date"></div>
                        </div>
                    </div>
                    
                    <h3>Backtrace</h3>
                    <div class="redirect-backtrace"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="media-modal-backdrop"></div> 