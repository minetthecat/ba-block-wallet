-- Apply once before deploying the wallet pages. Existing transactions stay completed.
ALTER TABLE `transaction` ADD COLUMN `status` ENUM('pending','completed','cancelled') NOT NULL DEFAULT 'completed';
