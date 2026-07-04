-- ============================================
-- RISER — Upgrade migration
-- Run this ONCE against an EXISTING riser_store database.
-- (Skip this file entirely if you are doing a fresh install from database.sql —
--  database.sql already includes everything below.)
-- ============================================

ALTER TABLE order_items
  ADD COLUMN custom_text VARCHAR(20) DEFAULT NULL AFTER line_total,
  ADD COLUMN thread_color VARCHAR(7) DEFAULT NULL AFTER custom_text,
  ADD COLUMN custom_fee DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER thread_color;

CREATE INDEX idx_products_active_featured ON products (is_active, is_featured);
CREATE INDEX idx_products_active_new ON products (is_active, is_new_arrival);
CREATE INDEX idx_products_created ON products (created_at);
CREATE INDEX idx_orders_status ON orders (status);
CREATE INDEX idx_orders_created ON orders (created_at);
CREATE INDEX idx_variants_stock ON product_variants (stock);
ALTER TABLE products ADD FULLTEXT INDEX ft_products_search (name, description);
