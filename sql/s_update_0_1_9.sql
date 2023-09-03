START TRANSACTION;
INSERT INTO `versions`(`version`, `date`) VALUES ('0.1.9','2023-09-03');
ALTER TABLE `statuses` ADD `shipmentId` INT NOT NULL AFTER `id`;
UPDATE `statuses` SET `shipmentId`= (SELECT id FROM shipments where shipments.number = shipmentNumber);
ALTER TABLE `statuses` DROP `shipmentNumber`;
ALTER TABLE `statuses` ADD INDEX `statuses_shipmentid_idx` (`shipmentId`);
COMMIT;