jQuery(document).ready(function($) {
    function showTab(tab) {
        $('.tab-button').removeClass('active');
        $('.tab-button[data-tab="' + tab + '"]').addClass('active');
        $('.tab-content').hide();
        $('#' + tab).show();
    }

    function formatText(text) {
        return $('<div>').text(text).html();
    }

    function refreshImportHistory() {
        $('#import-history').html('<p>Loading import history…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_fetch_import_history',
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#import-history').html('<p>Error loading import history.</p>');
                return;
            }

            const items = response.data;
            if (!items.length) {
                $('#import-history').html('<p>No imported posts found yet.</p>');
                return;
            }

            const rows = items.map(function(item) {
                return '<li><strong>' + formatText(item.title) + '</strong> – <a href="' + formatText(item.url) + '" target="_blank" rel="noopener">Source</a> <a href="' + formatText(item.edit) + '" target="_blank" rel="noopener">Edit</a> <span>(' + formatText(item.date) + ')</span></li>';
            });

            $('#import-history').html('<ul>' + rows.join('') + '</ul>');
        });
    }

    function refreshBannerList() {
        $('#banner-list').html('<p>Loading banners…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_fetch_banners',
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#banner-list').html('<p>Error loading banners.</p>');
                return;
            }

            const banners = response.data;
            if (!banners.length) {
                $('#banner-list').html('<p>No banners created yet.</p>');
                return;
            }

            const rows = banners.map(function(banner) {
                return '<div class="banner-card"><strong>' + formatText(banner.title) + '</strong> <span>(' + (banner.active ? 'Active' : 'Inactive') + ')</span><br><a href="' + formatText(banner.link) + '" target="_blank" rel="noopener">' + formatText(banner.link) + '</a>' + (banner.image ? '<div class="banner-image"><img src="' + formatText(banner.image) + '" alt="' + formatText(banner.title) + '"></div>' : '') + '</div>';
            });

            $('#banner-list').html(rows.join(''));
        });
    }

    function refreshAffiliatePanel() {
        $('#affiliate-metrics').html('<p>Loading affiliate dashboard…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_fetch_affiliate',
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#affiliate-metrics').html('<p>Error loading affiliate dashboard.</p>');
                return;
            }

            const data = response.data;
            $('#affiliate-metrics').html(
                '<div class="affiliate-stats"><div><strong>Total imported posts</strong><span>' + formatText(data.total_imported.toString()) + '</span></div><div><strong>Total banners</strong><span>' + formatText(data.total_banners.toString()) + '</span></div></div>'
            );

            $('#affiliate-code').val(data.settings.code || '');
            $('#affiliate-url').val(data.settings.partner_url || '');
            $('#affiliate-notes').val(data.settings.notes || '');
        });
    }

    $('.tab-button').on('click', function() {
        showTab($(this).data('tab'));
    });

    $('#crawl-form').on('submit', function(e) {
        e.preventDefault();
        const url = $(this).find('input[name="url"]').val().trim();
        if (!url) {
            $('#crawl-result').html('<p>Please enter a valid URL.</p>');
            return;
        }

        $('#crawl-result').html('<p>Fetching content…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_crawl',
            url: url,
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#crawl-result').html('<p>Error: ' + formatText(response.data.message || 'Unable to crawl this page.') + '</p>');
                return;
            }

            const result = response.data;
            $('#crawl-result').html(
                '<h3>Crawled: ' + formatText(result.title) + '</h3>' +
                '<div class="crawl-content">' + formatText(result.content.substring(0, 1000)) + '</div>' +
                '<button class="button button-primary import-btn" data-url="' + formatText(result.url) + '">Import as Post</button>'
            );
        });
    });

    $(document).on('click', '.import-btn', function() {
        const url = $(this).data('url');
        $('#import-result').html('<p>Importing content…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_import',
            url: url,
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#import-result').html('<p>Error: ' + formatText(response.data.message || 'Import failed.') + '</p>');
                return;
            }

            $('#import-result').html('<p>Imported successfully! <a href="' + formatText(response.data.edit_link) + '" target="_blank" rel="noopener">Edit post</a> (ID ' + formatText(response.data.post_id.toString()) + ')</p>');
            refreshImportHistory();
        });
    });

    $('#refresh-imports').on('click', function() {
        refreshImportHistory();
    });

    $('#export-json').on('click', function() {
        window.location = plagueHarvester.ajax_url + '?action=plague_harvester_export&format=json&security=' + encodeURIComponent(plagueHarvester.nonce);
    });

    $('#export-csv').on('click', function() {
        window.location = plagueHarvester.ajax_url + '?action=plague_harvester_export&format=csv&security=' + encodeURIComponent(plagueHarvester.nonce);
    });

    $('#banner-form').on('submit', function(e) {
        e.preventDefault();
        $('#banner-result').html('<p>Saving banner…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_save_banner',
            title: $('#banner-title').val(),
            image: $('#banner-image').val(),
            link: $('#banner-link').val(),
            active: $('#banner-active').is(':checked') ? 1 : 0,
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#banner-result').html('<p>Error: ' + formatText(response.data.message || 'Unable to save banner.') + '</p>');
                return;
            }

            $('#banner-result').html('<p>Banner saved.</p>');
            $('#banner-form')[0].reset();
            refreshBannerList();
        });
    });

    $('#affiliate-form').on('submit', function(e) {
        e.preventDefault();
        $('#affiliate-result').html('<p>Saving affiliate settings…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_save_affiliate',
            code: $('#affiliate-code').val(),
            partner_url: $('#affiliate-url').val(),
            notes: $('#affiliate-notes').val(),
            security: plagueHarvester.nonce
        }, function(response) {
            if (!response.success) {
                $('#affiliate-result').html('<p>Error: ' + formatText(response.data.message || 'Unable to save affiliate settings.') + '</p>');
                return;
            }

            $('#affiliate-result').html('<p>Affiliate settings saved.</p>');
            refreshAffiliatePanel();
        });
    });

    refreshImportHistory();
    refreshBannerList();
    refreshAffiliatePanel();
});
