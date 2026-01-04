DELIMITER $$

-- ============================================================
-- 1. Triggers pour FactoryOrder
-- ============================================================

DROP TRIGGER IF EXISTS prevent_factory_order_update$$
CREATE TRIGGER prevent_factory_order_update
BEFORE UPDATE ON FactoryOrder
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Modification interdite (FactoryOrder).';
END$$

DROP TRIGGER IF EXISTS prevent_factory_order_delete$$
CREATE TRIGGER prevent_factory_order_delete
BEFORE DELETE ON FactoryOrder
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Suppression interdite (FactoryOrder).';
END$$

-- ============================================================
-- 2. Triggers pour Invoice (Factures)
-- ============================================================

DROP TRIGGER IF EXISTS before_invoice_insert$$
CREATE TRIGGER before_invoice_insert
BEFORE INSERT ON Invoice
FOR EACH ROW
BEGIN
    DECLARE v_id_SaveCustomer INT;
    -- FIX COLLATION: On force le charset des variables pour matcher la table
    DECLARE v_today_prefix VARCHAR(20) CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
    DECLARE v_max_invoice VARCHAR(50) CHARSET utf8mb4 COLLATE utf8mb4_general_ci;
    DECLARE v_next_seq INT DEFAULT 1;

    -- Récupération client
    SELECT c.id_SaveCustomer INTO v_id_SaveCustomer
    FROM CustomerOrder co
    JOIN Customer c ON co.id_Customer = c.id_Customer
    WHERE co.id_Order = NEW.id_Order LIMIT 1; 
    SET NEW.id_SaveCustomer = v_id_SaveCustomer;

    -- Numérotation
    SET v_today_prefix = CONCAT('FACT-', DATE_FORMAT(NOW(), '%Y%m%d'), '-');
    SELECT invoice_number INTO v_max_invoice FROM Invoice WHERE invoice_number LIKE CONCAT(v_today_prefix, '%') ORDER BY id_Invoice DESC LIMIT 1;
    IF v_max_invoice IS NOT NULL THEN SET v_next_seq = CAST(SUBSTRING_INDEX(v_max_invoice, '-', -1) AS UNSIGNED) + 1; END IF;
    SET NEW.invoice_number = CONCAT(v_today_prefix, LPAD(v_next_seq, 3, '0'));
END$$

DROP TRIGGER IF EXISTS prevent_invoice_update$$
CREATE TRIGGER prevent_invoice_update
BEFORE UPDATE ON Invoice
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Modification interdite (Invoice).';
END$$

DROP TRIGGER IF EXISTS prevent_invoice_delete$$
CREATE TRIGGER prevent_invoice_delete
BEFORE DELETE ON Invoice
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Suppression interdite (Invoice).';
END$$

-- ============================================================
-- 3. Triggers pour OrderItem
-- ============================================================

DROP TRIGGER IF EXISTS prevent_orderitem_update$$
CREATE TRIGGER prevent_orderitem_update
BEFORE UPDATE ON OrderItem
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Modification interdite (OrderItem).';
END$$

DROP TRIGGER IF EXISTS prevent_orderitem_delete$$
CREATE TRIGGER prevent_orderitem_delete
BEFORE DELETE ON OrderItem
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Suppression interdite (OrderItem).';
END$$

-- ============================================================
-- 4. Triggers pour SaveCustomer
-- ============================================================

DROP TRIGGER IF EXISTS prevent_savecustomer_update$$
CREATE TRIGGER prevent_savecustomer_update
BEFORE UPDATE ON SaveCustomer
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Modification interdite (SaveCustomer).';
END$$

DROP TRIGGER IF EXISTS prevent_savecustomer_delete$$
CREATE TRIGGER prevent_savecustomer_delete
BEFORE DELETE ON SaveCustomer
FOR EACH ROW
BEGIN
    SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Erreur : Suppression interdite (SaveCustomer).';
END$$

DELIMITER ;