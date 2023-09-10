START TRANSACTION;
INSERT INTO `versions`(`version`, `date`) VALUES ('0.1.10','2023-09-03');
ALTER TABLE `shipments` DROP `userId`;
ALTER TABLE `statuses` ADD INDEX `statuses_date_idx` (`date`);
ALTER TABLE `shipments` ADD `isArchieved` BOOLEAN NOT NULL DEFAULT FALSE AFTER `orgId`;
ALTER TABLE `shipments` ADD INDEX `shipments_orgid_isarchieved_idx` (`orgId`, `isArchieved`);
COMMIT;