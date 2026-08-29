UPDATE listings SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE articles SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE media_files SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE listing_requests SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE listing_reports SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE ai_preferences SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE payment_transactions SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
UPDATE site_settings SET is_test = false WHERE is_test = true AND deleted_at IS NULL;

DELETE FROM tariffs t
WHERE t.is_test = true
  AND EXISTS (SELECT 1 FROM tariffs p WHERE p.code = t.code AND p.is_test = false AND p.deleted_at IS NULL);
UPDATE tariffs SET is_test = false WHERE is_test = true AND deleted_at IS NULL;

DELETE FROM info_pages t
WHERE t.is_test = true
  AND EXISTS (SELECT 1 FROM info_pages p WHERE p.slug = t.slug AND p.is_test = false AND p.deleted_at IS NULL);
UPDATE info_pages SET is_test = false WHERE is_test = true AND deleted_at IS NULL;

DELETE FROM seo_meta t
WHERE t.is_test = true
  AND EXISTS (
    SELECT 1 FROM seo_meta p
    WHERE p.page_key = t.page_key AND p.locale = t.locale AND p.is_test = false AND p.deleted_at IS NULL
  );
UPDATE seo_meta SET is_test = false WHERE is_test = true AND deleted_at IS NULL;
