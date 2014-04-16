DELETE FROM `module` WHERE `name` = 'Payment';

DROP TABLE IF EXISTS `payment_transaction_item`;
DROP TABLE IF EXISTS `payment_shopping_cart`;
DROP TABLE IF EXISTS `payment_module`;
DROP TABLE IF EXISTS `payment_exchange_rate`;
DROP TABLE IF EXISTS `payment_transaction`;
DROP TABLE IF EXISTS `payment_currency`;
DROP TABLE IF EXISTS `payment_type`;
DROP TABLE IF EXISTS `payment_discount_cupon`;

/* TEST DATA */
DELETE FROM `module` WHERE `name` = 'Membership';