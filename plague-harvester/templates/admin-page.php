<div class="plague-harvester">
    <div class="header">
        <img src="<?php echo esc_url(PLAGUE_HARVESTER_URL); ?>assets/images/logo.png" alt="Plague Harvester">
        <div>
            <h1>Plague Harvester</h1>
            <p>Steal the web. Cure your content hunger.</p>
        </div>
    </div>

    <div class="tabs">
        <button class="tab-button active" data-tab="crawl">Crawl</button>
        <button class="tab-button" data-tab="import">Import</button>
        <button class="tab-button" data-tab="export">Export</button>
        <button class="tab-button" data-tab="banners">Banners</button>
        <button class="tab-button" data-tab="affiliate">Affiliate</button>
    </div>

    <div class="tab-content" id="crawl">
        <form id="crawl-form">
            <input type="url" name="url" placeholder="https://example.com" required>
            <button type="submit" class="button button-primary">Crawl</button>
        </form>
        <div id="crawl-result"></div>
    </div>

    <div class="tab-content" id="import" style="display: none;">
        <h2>Import Crawled Content</h2>
        <div id="import-result"></div>
        <div id="import-history">
            <p>Loading import history…</p>
        </div>
        <button id="refresh-imports" class="button">Refresh Import History</button>
    </div>

    <div class="tab-content" id="export" style="display: none;">
        <h2>Export Imported Content</h2>
        <p>Download posts imported through Plague Harvester.</p>
        <button id="export-json" class="button button-primary">Download JSON</button>
        <button id="export-csv" class="button">Download CSV</button>
        <div id="export-result"></div>
    </div>

    <div class="tab-content" id="banners" style="display: none;">
        <h2>Banner Management</h2>
        <form id="banner-form">
            <div class="form-row">
                <label for="banner-title">Banner Title</label>
                <input id="banner-title" name="title" type="text" required>
            </div>
            <div class="form-row">
                <label for="banner-image">Image URL</label>
                <input id="banner-image" name="image" type="url" placeholder="https://example.com/banner.png">
            </div>
            <div class="form-row">
                <label for="banner-link">Destination URL</label>
                <input id="banner-link" name="link" type="url" placeholder="https://example.com/?ref=affiliate" required>
            </div>
            <div class="form-row checkbox-row">
                <label><input type="checkbox" id="banner-active" name="active"> Active</label>
            </div>
            <button type="submit" class="button button-primary">Save Banner</button>
        </form>
        <div id="banner-result"></div>
        <div id="banner-list">
            <p>Loading banners…</p>
        </div>
    </div>

    <div class="tab-content" id="affiliate" style="display: none;">
        <h2>Affiliate Dashboard</h2>
        <div id="affiliate-metrics">
            <p>Loading affiliate stats…</p>
        </div>
        <form id="affiliate-form">
            <div class="form-row">
                <label for="affiliate-code">Affiliate Code</label>
                <input id="affiliate-code" name="code" type="text" placeholder="YOUR-AFFILIATE-CODE">
            </div>
            <div class="form-row">
                <label for="affiliate-url">Affiliate Landing URL</label>
                <input id="affiliate-url" name="partner_url" type="url" placeholder="https://example.com/referral">
            </div>
            <div class="form-row">
                <label for="affiliate-notes">Notes</label>
                <textarea id="affiliate-notes" name="notes" rows="4"></textarea>
            </div>
            <button type="submit" class="button button-primary">Save Affiliate Settings</button>
        </form>
        <div id="affiliate-result"></div>
    </div>
</div>
