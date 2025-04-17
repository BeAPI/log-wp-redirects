<script>
var LWR_Network = {
    response: [],
    query_args: {
        'orderby': 'date_added',
        'order': 'DESC',
        'page': 1,
        'blog_id': 0
    },
    nonce: '<?php echo wp_create_nonce( 'lwr_network_nonce' ); ?>'
};
</script>

<div class="wrap">
    <h2>Network Log WP Redirects</h2>
    <p>View and manage redirections across all sites in the network.</p>
    
    <div class="lwr-network-stats">
        <h3>Redirects per site</h3>
        <table class="widefat lwr-stats-table">
            <thead>
                <tr>
                    <th>Site</th>
                    <th>Redirects</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stats = LWR()->query->get_redirects_count_per_blog();
                if (empty($stats)): 
                ?>
                <tr>
                    <td colspan="3">No redirects have been logged yet.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?php echo esc_html($stat['blog_name']); ?> (ID: <?php echo esc_html($stat['blog_id']); ?>)</td>
                    <td><?php echo esc_html($stat['count']); ?></td>
                    <td>
                        <button class="button lwr-view-site" data-blog-id="<?php echo esc_attr($stat['blog_id']); ?>">View</button>
                        <button class="button lwr-clear-site" data-blog-id="<?php echo esc_attr($stat['blog_id']); ?>">Clear</button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="lwr-actions">
        <div class="lwr-filter">
            <label for="blog-filter">Filter by site:</label>
            <select id="blog-filter" onchange="LWR_Network.filterByBlog(this.value)">
                <option value="0">All sites</option>
                <?php 
                $blogs = LWR()->query->get_available_blogs();
                foreach ($blogs as $blog_id => $blog_name) {
                    echo '<option value="' . esc_attr($blog_id) . '">' . esc_html($blog_name) . '</option>';
                }
                ?>
            </select>
        </div>
        
        <div class="lwr-buttons">
            <button class="button lwr-clear-all" onclick="LWR_Network.clearAll()">Clear All</button>
            <button class="button lwr-refresh" onclick="LWR_Network.refresh()">Refresh</button>
        </div>
    </div>
    
    <div class="lwr-pager"></div>
    <table class="widefat lwr-listing">
        <thead>
            <tr>
                <td>Redirect URL</td>
                <td title="HTTP status code">Status</td>
                <td>From URL</td>
                <td>Site</td>
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
                            <h3>Site Info</h3>
                            <div class="redirect-blog-info"></div>
                        </div>
                        <div class="box">
                            <h3>User Info</h3>
                            <div class="redirect-user-info"></div>
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
                            <h3>Connection Info</h3>
                            <div class="redirect-connection-info"></div>
                        </div>
                        <div class="box">
                            <h3>IP Address</h3>
                            <div class="redirect-ip-address"></div>
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

<script>
(function($) {
    $(function() {
        // Refresh
        LWR_Network.refresh = function() {
            $('.lwr-refresh').text('Refreshing...').attr('disabled', 'disabled');

            $.post(ajaxurl, {
                'action': 'lwr_network_query',
                '_wpnonce': LWR_Network.nonce,
                'data': LWR_Network.query_args
            }, function(data) {
                LWR_Network.response = data;
                
                var html = '';
                if (data.rows.length === 0) {
                    html = '<tr><td colspan="6">No redirects found.</td></tr>';
                } else {
                    $.each(data.rows, function(idx, row) {
                        html += `
                        <tr>
                            <td class="field-location">
                                <div><a href="javascript:;" data-id="` + idx + `">` + row.location + `</a></div>
                            </td>
                            <td class="field-status">` + row.status + `</td>
                            <td class="field-request-uri">` + row.request_uri + `</td>
                            <td class="field-blog-name">` + row.blog_name + `</td>
                            <td class="field-username">` + row.username + `</td>
                            <td class="field-date" title="` + row.date_raw + `">` + row.date_added + `</td>
                        </tr>
                        `;
                    });
                }
                $('.lwr-listing tbody').html(html);
                $('.lwr-pager').html(data.pager);
                $('.lwr-refresh').text('Refresh').removeAttr('disabled');
            }, 'json');
        }

        // Filter by blog
        LWR_Network.filterByBlog = function(blogId) {
            LWR_Network.query_args.blog_id = parseInt(blogId);
            LWR_Network.query_args.page = 1;
            LWR_Network.refresh();
        }
        
        // View site redirects
        $(document).on('click', '.lwr-view-site', function() {
            var blogId = $(this).data('blog-id');
            LWR_Network.filterByBlog(blogId);
            
            // Scroll to the table
            $('html, body').animate({
                scrollTop: $('.lwr-listing').offset().top
            }, 500);
        });

        // Clear site redirects
        $(document).on('click', '.lwr-clear-site', function() {
            if (!confirm('Are you sure you want to clear all redirects for this site?')) {
                return;
            }
            
            var blogId = $(this).data('blog-id');
            var $button = $(this);
            
            $button.text('Clearing...').attr('disabled', 'disabled');

            $.post(ajaxurl, {
                'action': 'lwr_network_clear',
                '_wpnonce': LWR_Network.nonce,
                'data': {
                    'blog_id': blogId
                }
            }, function(data) {
                // Reload the page to update stats
                location.reload();
            }, 'json');
        });
        
        // Clear all redirects
        LWR_Network.clearAll = function() {
            if (!confirm('Are you sure you want to clear all redirects for ALL sites?')) {
                return;
            }
            
            $('.lwr-clear-all').text('Clearing...').attr('disabled', 'disabled');

            $.post(ajaxurl, {
                'action': 'lwr_network_clear',
                '_wpnonce': LWR_Network.nonce,
                'data': {
                    'blog_id': 0
                }
            }, function(data) {
                // Reload the page to update stats
                location.reload();
            }, 'json');
        }

        LWR_Network.show_details = function(action) {
            var id = LWR_Network.active_id;

            if ('next' == action && id < LWR_Network.response.rows.length - 1) {
                id = id + 1;
            }
            else if ('prev' == action && id > 0) {
                id = id - 1;
            }

            LWR_Network.active_id = id;

            var data = LWR_Network.response.rows[id];
            $('.redirect-location').text(data.location);
            $('.redirect-id').text(id);
            $('.redirect-status').text('HTTP ' + data.status);
            $('.redirect-referer').text(data.referer || 'N/A');
            $('.redirect-request-uri').text(data.request_uri || 'N/A');
            $('.redirect-blog-info').html('Blog ID: ' + data.blog_id + '<br>Blog Name: ' + data.blog_name);
            $('.redirect-user-info').html('User ID: ' + data.user_id + '<br>Username: ' + data.username);
            $('.redirect-connection-info').html('User Agent: ' + data.user_agent);
            $('.redirect-ip-address').html(data.ip_address || 'N/A');
            $('.redirect-cookies').html(data.cookies ? data.cookies : 'No cookies');
            
            // Process backtrace
            var backtrace = JSON.parse(data.backtrace);
            var backtraceHtml = '';
            
            if (backtrace && backtrace.length) {
                backtraceHtml += '<ol>';
                backtrace.forEach(function(item) {
                    backtraceHtml += '<li>';
                    if (item.file) {
                        backtraceHtml += '<strong>' + item.file + ':' + item.line + '</strong><br>';
                    }
                    if (item.class) {
                        backtraceHtml += item.class + '::' + item.function + '()';
                    } else if (item.function) {
                        backtraceHtml += item.function + '()';
                    }
                    backtraceHtml += '</li>';
                });
                backtraceHtml += '</ol>';
            } else {
                backtraceHtml = 'No backtrace available';
            }
            
            $('.redirect-backtrace').html(backtraceHtml);
            
            $('.media-modal').addClass('open');
            $('.media-modal-backdrop').addClass('open');  
        }

        // Page change
        $(document).on('click', '.lwr-page:not(.active)', function() {
            LWR_Network.query_args.page = parseInt($(this).attr('data-page'));
            LWR_Network.refresh();
        });

        // Open detail modal
        $(document).on('click', '.field-location a', function() {
            LWR_Network.active_id = parseInt($(this).attr('data-id'));
            LWR_Network.show_details('curr');
        });

        // Close modal window
        $(document).on('click', '.media-modal-close', function() {
            var $this = $(this);

            if ($this.hasClass('prev') || $this.hasClass('next')) {
                var action = $this.hasClass('prev') ? 'prev' : 'next';
                LWR_Network.show_details(action);
                return;
            }

            $('.media-modal').removeClass('open');
            $('.media-modal-backdrop').removeClass('open');
            $(document).off('keydown.lwr-modal-close');
        });

        $(document).keydown(function(e) {

            if (! $('.media-modal').hasClass('open')) {
                return;
            }

            if (-1 < $.inArray(e.keyCode, [27, 38, 40])) {
                e.preventDefault();

                if (27 == e.keyCode) { // esc
                    $('.media-modal-close').click();
                }
                else if (38 == e.keyCode) { // up
                    $('.media-modal-close.prev').click();
                }
                else if (40 == e.keyCode) { // down
                    $('.media-modal-close.next').click();
                }
            }
        });

        // Init
        LWR_Network.refresh();
    });
})(jQuery);
</script> 