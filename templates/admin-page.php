<div class="plague-harvester">
    <div class="header">
        <img src="<?php echo PLAGUE_HARVESTER_URL; ?>assets/images/logo.png" alt="Plague Harvester">
        <h1>Plague Harvester</h1>
        <p>Steal the web. Cure your content hunger.</p>
    </div>

    <div class="tabs">
        <button class="tab-button active" data-tab="crawl">Crawl</button>
        <button class="tab-button" data-tab="import">Import</button>
        <button class="tab-button" data-tab="export">Export</button>
    </div>

    <div class="tab-content" id="crawl">
        <form id="crawl-form">
            <input type="url" name="url" placeholder="https://example.com" required>
            <button type="submit">Crawl</button>
        </form>
        <div id="crawl-result"></div>
    </div>

    <div class="tab-content" id="import" style="display: none;">
        <p>Import crawled content into WordPress.</p>
    </div>

    <div class="tab-content" id="export" style="display: none;">
        <p>Export content to CSV, JSON, or WXR.</p>
    </div>
</div>
<div class="tab-content" id="import" style="display: none;">
    <h2>Import Crawled Content</h2>
    <div id="import-history">
        <p>No imports yet. Crawl some content first.</p>
    </div>
    <button id="refresh-imports">Refresh</button>
</div>