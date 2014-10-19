ALTER TABLE `tblproductdata` ADD `intProductStock` INT(10) NOT NULL AFTER `strProductCode`,
ADD `decProductCost` DECIMAL(10,2) NOT NULL AFTER `intProductStock`;
