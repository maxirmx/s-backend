START TRANSACTION;
INSERT INTO `versions`(`version`, `date`) VALUES ('0.1.13','2023-09-20');
CREATE TABLE `user_org_mappings` ( `id` INT NOT NULL AUTO_INCREMENT , `userId` INT NOT NULL , `orgId` INT NOT NULL , PRIMARY KEY (`id`)) ENGINE = InnoDB;
ALTER TABLE `user_org_mappings` ADD INDEX `mapping_user_idx` (`userId`);
ALTER TABLE `user_org_mappings` ADD INDEX `mapping_org_idx` (`orgId`);
INSERT INTO `user_org_mappings` (`userId`, `orgId`) SELECT `id`, `orgId` FROM `users`;
COMMIT;