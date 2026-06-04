jQuery(document).ready(function($) {
    // Tab switching
    $('.tab-button').on('click', function() {
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').hide();
        $('#' + $(this).data('tab')).show();
    });

    // Crawl form
    $('#crawl-form').on('submit', function(e) {
        e.preventDefault();
        const url = $(this).find('input[name="url"]').val();
        $.post(ajaxurl, {
            action: 'plague_harvester_crawl',
            url: url,
            security: plagueHarvester.nonce
        }, function(response) {
            $('#crawl-result').html(`
                <h3>Crawled: ${response.title}</h3>
                <div>${response.content.substring(0, 200)}...</div>
                <button class="import-btn" data-url="${response.url}">Import as Post</button>
            `);
        });
    });

    // Import button
    $(document).on('click', '.import-btn', function() {
        const url = $(this).data('url');
        $.post(ajaxurl, {
            action: 'plague_harvester_import',
            url: url,
            security: plagueHarvester.nonce
        }, function(response) {
            alert('Imported! Post ID: ' + response);
        });
    });
});