-- LipaByte — fix default account passwords (Admin@123 / Student@123)
-- Run in phpMyAdmin if login fails with correct credentials.

UPDATE `users`
SET `password_hash` = '$2y$10$yoczB3sNFi4tIBe6ayHiH.ZfX5zybfl6xzU5tAI6M7PnplgQlnWEG'
WHERE `email` = 'admin@lipabyte.edu.ph';

UPDATE `users`
SET `password_hash` = '$2y$10$Svf470VtPgYsoAU/Kg5druO1RJH8qsLUSQbEIoAGwP82ExW2PHrUm'
WHERE `email` IN ('juan.mitra@university.edu.ph', 'maria.garcia@university.edu.ph');
