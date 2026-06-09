jQuery(document).ready(function($) {
    function showTab(tab) {
        $('.tab-button').removeClass('active');
        $('.tab-button[data-tab="' + tab + '"]').addClass('active');
        $('.tab-content').hide();
        $('#' + tab).show();
    }

    function refreshImportHistory() {
        $('#import-history').html('<p>Loading import history…</p>');

        $.post(plagueHarvester.ajax_url, {
            action: 'plague_harvester_fetch_import_history',
            security: plagueHarvester.nonce
        }, function(response) {
            if (response.success) {
                const items = response.data;
                if (!items.length) {
                    $('#import-history').html('<p>No imported posts found yet.</p>');
                    return;
                }

                const rows = items.map(function(item) {
                    return '<li><strong>' + item.title + '</strong> – <a href="' + item.url + '" target="_blank" rel="noopener">Source</a> <a href="' + item.edit + '" target="_blank" rel="noopener">Edit</a> <span>(' + item.date + ')</span></li>';
                });
                $('#import-history').html('<ul>' + rows.join('') + '</ul>');
            } else {
                $('#import-history').html('<p>Error loading import history.</p>');
            }
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
                $('#crawl-result').html('<p>Error: ' + (response.data.message || 'Unable to crawl this page.') + '</p>');
                return;
            }

            const result = response.data;
            $('#crawl-result').html(
                '<h3>Crawled: ' + $('<div>').text(result.title).html() + '</h3>' +
                '<div class="crawl-content">' + $('<div>').text(result.content).html() + '</div>' +
                '<button class="button button-primary import-btn" data-url="' + $('<div>').text(result.url).html() + '">Import as Post</button>'
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
                $('#import-result').html('<p>Error: ' + (response.data.message || 'Import failed.') + '</p>');
                return;
            }

            $('#import-result').html('<p>Imported successfully! <a href="' + response.data.edit_link + '" target="_blank" rel="noopener">Edit post</a> (ID ' + response.data.post_id + ')</p>');
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

    refreshImportHistory();
});