(function($) {
    $(function() {

        // Refresh
        LWR.refresh = function() {
            $('.lwr-refresh').text('Refreshing...').attr('disabled', 'disabled');

            $.post(ajaxurl, {
                'action': 'lwr_query',
                '_wpnonce': LWR.nonce,
                'data': LWR.query_args
            }, function(data) {
                LWR.response = data;
                
                var html = '';
                $.each(data.rows, function(idx, row) {
                    html += `
                    <tr>
                        <td class="field-location">
                            <div><a href="javascript:;" data-id="` + idx + `">` + row.location + `</a></div>
                        </td>
                        <td class="field-status">` + row.status + `</td>
                        <td class="field-request-uri">` + row.request_uri + `</td>
                        <td class="field-username">` + row.username + `</td>
                        <td class="field-date" title="` + row.date_raw + `">` + row.date_added + `</td>
                    </tr>
                    `;
                });
                $('.lwr-listing tbody').html(html);
                $('.lwr-pager').html(data.pager);
                $('.lwr-refresh').text('Refresh').removeAttr('disabled');
            }, 'json');
        }

        // Clear
        LWR.clear = function() {
            $('.lwr-clear').text('Clearing...').attr('disabled', 'disabled');

            $.post(ajaxurl, {
                'action': 'lwr_clear',
                '_wpnonce': LWR.nonce
            }, function(data) {
                $('.lwr-listing tbody').html('');
                $('.lwr-clear').text('Clear log').removeAttr('disabled');
            }, 'json');
        }

        LWR.show_details = function(action) {
            var id = LWR.active_id;

            if ('next' == action && id < LWR.response.rows.length - 1) {
                id = id + 1;
            }
            else if ('prev' == action && id > 0) {
                id = id - 1;
            }

            LWR.active_id = id;

            var data = LWR.response.rows[id];
            $('.redirect-location').text(data.location);
            $('.redirect-id').text(id);
            $('.redirect-status').text('HTTP ' + data.status);
            $('.redirect-referer').text(data.referer || 'N/A');
            $('.redirect-request-uri').text(data.request_uri || 'N/A');
            $('.redirect-user-info').html('User ID: ' + data.user_id + '<br>Username: ' + data.username);
            $('.redirect-connection-info').html('User Agent: ' + data.user_agent);
            $('.redirect-ip-address').html(data.ip_address || 'N/A');
            $('.redirect-cookies').html(data.cookies ? data.cookies : 'No cookies');
            $('.redirect-date').html(data.date_raw);
            
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
            LWR.query_args.page = parseInt($(this).attr('data-page'));
            LWR.refresh();
        });

        // Open detail modal
        $(document).on('click', '.field-location a', function() {
            LWR.active_id = parseInt($(this).attr('data-id'));
            LWR.show_details('curr');
        });

        // Close modal window
        $(document).on('click', '.media-modal-close', function() {
            var $this = $(this);

            if ($this.hasClass('prev') || $this.hasClass('next')) {
                var action = $this.hasClass('prev') ? 'prev' : 'next';
                LWR.show_details(action);
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

        // Ajax
        LWR.refresh();
    });
})(jQuery); 