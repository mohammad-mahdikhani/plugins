INSERT INTO licenses (
  plan, list_price, discount, payable, buyer_name, phone,
  starts_at, ends_at, payment_method, user_id, created_at, status
)
SELECT '50y', 0, 0, 0, 'استودیو', '09120000000',
  '2026-01-01T00:00:00+03:30', '2076-10-29T00:00:00+03:30',
  'cash', 1, '2026-01-01T00:00:00+03:30', 'paid'
WHERE NOT EXISTS (
  SELECT 1 FROM licenses
  WHERE status='paid' AND plan!='7d' AND ends_at > '2026-10-29T00:00:00+03:30'
);

INSERT OR REPLACE INTO settings (key, value) VALUES ('trial_started_at', '2026-01-01T00:00:00+03:30');
INSERT OR REPLACE INTO settings (key, value) VALUES ('trial_ends_at', '2076-10-29T00:00:00+03:30');
INSERT OR REPLACE INTO settings (key, value) VALUES ('studio_unlock', '1');
