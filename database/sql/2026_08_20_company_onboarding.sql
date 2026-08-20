ALTER TABLE `companies`
  ADD COLUMN `onboarding_completed_at` TIMESTAMP NULL AFTER `status`;

UPDATE `companies`
SET `onboarding_completed_at` = NOW()
WHERE `onboarding_completed_at` IS NULL
  AND EXISTS (
    SELECT 1
    FROM `branches`
    WHERE `branches`.`company_id` = `companies`.`id`
  );
