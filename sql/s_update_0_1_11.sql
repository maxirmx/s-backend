START TRANSACTION;
INSERT INTO `versions`(`version`, `date`) VALUES ('0.1.11','2023-09-12');
INSERT INTO `versions`(`version`, `date`) VALUES ('0.1.12','2023-09-17');
ALTER TABLE `shipments` CHANGE `number` `number` VARCHAR(128) CHARACTER SET utf8 COLLATE utf8_unicode_520_ci NOT NULL;
COMMIT;