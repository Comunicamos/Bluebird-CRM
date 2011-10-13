-- Deletes aren't cascaded (possibly a good thing) so we can't use foreign keys
-- here without running into referential integrity issues or rewriting a bunch
-- of civicrm core code...

-- --------------------------------------------
-- CREATE shadow tables
-- --------------------------------------------
DROP TABLE IF EXISTS shadow_contact;
CREATE TABLE shadow_contact (
    contact_id int(10) unsigned PRIMARY KEY,
    first_name varchar(255),
    middle_name varchar(255),
    last_name varchar(255),
    household_name varchar(255),
    organization_name varchar(255),
    suffix_id varchar(255),
    birth_date date,
    contact_type varchar(255),
    INDEX (first_name, middle_name, last_name),
    INDEX (household_name),
    INDEX (organization_name),
    INDEX (birth_date)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS shadow_address;
CREATE TABLE shadow_address (
    address_id int(10) unsigned PRIMARY KEY,
    contact_id int(10) unsigned,
    street_address varchar(255),
    postal_code varchar(255),
    city varchar(255),
    INDEX (street_address),
    INDEX (postal_code),
    INDEX (city)
);


-- Change the delimiter to make stored triggers/functions easier to write!
DELIMITER |

-- -----------------------------
-- Stored Utility Functions
-- -----------------------------
DROP FUNCTION IF EXISTS BB_NORMALIZE |
CREATE FUNCTION BB_NORMALIZE (value VARCHAR(255))
    RETURNS VARCHAR(255) DETERMINISTIC

    BEGIN
        -- Compress '' values into null
        IF value IS NULL OR value = '' THEN
            RETURN NULL;
        END IF;

        -- Strip all  punctuation and spaces from strings
        RETURN LCASE(REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( value,
                    ',', ''),
                   '\'', ''),
                    '.', ''),
                    '-', ''),
                    ';', ''),
                    ':', ''),
                    '#', ''),
                    ' ', ''));
    END
|

DROP FUNCTION IF EXISTS BB_NORMALIZE_ADDR |
CREATE FUNCTION BB_NORMALIZE_ADDR (value VARCHAR(255))
    RETURNS VARCHAR(255) DETERMINISTIC

    BEGIN
        DECLARE address VARCHAR(255);

        -- Compress '' values into null
        IF value IS NULL OR value = '' THEN
            RETURN NULL;
        END IF;

        -- Strip all punctuation, abbreviate address parts for consistency and replace the spaces

        -- Strip out all the ordinals from the street numbers
        SET address = preg_replace('/(?<=[0-9])(?:st|nd|rd|th)/','', TRIM(LCASE(value)));

        -- Standardize spacing from the street numbers from 7B, 7-B, 7 B => 7 B
        SET address = preg_replace('/^(\d+)-?(\w+)\s/', '$1 $2 ', address);

        -- Strip out all the different kinds of punctuation
        -- SPECIAL: Don't replace 's with spaces
        SET address = REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( address,
                    ',', ' '),
                    '\'', ''),
                    '.', ' '),
                    '-', ' '),
                    ';', ' '),
                    ':', ' '),
                    '#', ' ');

        -- Pad with spaces for most accurate part matching
        SET address = CONCAT(' ', address, ' ');

        -- Abbeviate all the possible address parts for consistency
        SET address = REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( REPLACE( address ,
                    ' street ', ' st ' ),
                    ' road ', ' rd ' ),
                    ' boulevard ', ' blvd ' ),
                    ' meadows ', ' mdws '),
                    ' avenue ', ' ave ' ),
                    ' terrace ', ' ter ' ),
                    ' court ', ' ct '),
                    ' circle ', ' cir '),
                    ' crescent ', ' cres '),
                    ' parkway ', ' pkwy ' ),
                    ' west ', ' w ' ),
                    ' east ', ' e ' ),
                    ' north ', ' n ' ),
                    ' south ', ' s ' ),
                    ' apartment ', ' ' ),
                    ' apt ', ' ' ),
                    ' place ', ' pl ' ),
                    ' penthouse ', ' ph ' ),
                    ' lane ', ' ln '),
                    ' drive ', ' dr ');

        -- Normalize the spaces on the way out the door
        RETURN preg_replace('/ +/', ' ', TRIM(address));
    END
|

-- -----------------------------
-- Triggers for shadow contact
-- -----------------------------
DROP TRIGGER IF EXISTS shadow_contact_insert_trigger |
CREATE TRIGGER shadow_contact_insert_trigger AFTER INSERT ON civicrm_contact
    FOR EACH ROW BEGIN
        DECLARE norm_first_name VARCHAR(255);
        DECLARE norm_middle_name VARCHAR(255);
        DECLARE norm_last_name VARCHAR(255);
        DECLARE norm_household_name VARCHAR(255);
        DECLARE norm_organization_name VARCHAR(255);

        SET norm_first_name = BB_NORMALIZE(NEW.first_name);
        SET norm_middle_name = BB_NORMALIZE(NEW.middle_name);
        SET norm_last_name = BB_NORMALIZE(NEW.last_name);
        SET norm_household_name = BB_NORMALIZE(NEW.household_name);
        SET norm_organization_name = BB_NORMALIZE(NEW.organization_name);

        INSERT INTO shadow_contact
                    (contact_id, first_name, middle_name, last_name, suffix_id, birth_date, contact_type, household_name, organization_name)
             VALUES (NEW.id, norm_first_name, norm_middle_name, norm_last_name, NEW.suffix_id, NEW.birth_date, NEW.contact_type, norm_household_name, norm_organization_name)
             ON DUPLICATE KEY UPDATE
                    first_name=norm_first_name,
                    middle_name=norm_middle_name,
                    last_name=norm_last_name,
                    suffix_id=NEW.suffix_id,
                    birth_date=NEW.birth_date,
                    contact_type=NEW.contact_type,
                    household_name=norm_household_name,
                    organization_name=norm_organization_name;
    END
|

DROP TRIGGER IF EXISTS shadow_contact_update_trigger |
CREATE TRIGGER shadow_contact_update_trigger AFTER UPDATE ON civicrm_contact
    FOR EACH ROW BEGIN
        DECLARE norm_first_name VARCHAR(255);
        DECLARE norm_middle_name VARCHAR(255);
        DECLARE norm_last_name VARCHAR(255);
        DECLARE norm_household_name VARCHAR(255);
        DECLARE norm_organization_name VARCHAR(255);

        SET norm_first_name = BB_NORMALIZE(NEW.first_name);
        SET norm_middle_name = BB_NORMALIZE(NEW.middle_name);
        SET norm_last_name = BB_NORMALIZE(NEW.last_name);
        SET norm_household_name = BB_NORMALIZE(NEW.household_name);
        SET norm_organization_name = BB_NORMALIZE(NEW.organization_name);

        INSERT INTO shadow_contact
                    (contact_id, first_name, middle_name, last_name, suffix_id, birth_date, contact_type, household_name, organization_name)
             VALUES (NEW.id, norm_first_name, norm_middle_name, norm_last_name, NEW.suffix_id, NEW.birth_date, NEW.contact_type, norm_household_name, norm_organization_name)
             ON DUPLICATE KEY UPDATE
                    first_name=norm_first_name,
                    middle_name=norm_middle_name,
                    last_name=norm_last_name,
                    suffix_id=NEW.suffix_id,
                    birth_date=NEW.birth_date,
                    contact_type=NEW.contact_type,
                    household_name=norm_household_name,
                    organization_name=norm_organization_name;
    END
|


DROP TRIGGER IF EXISTS shadow_contact_delete_trigger |
CREATE TRIGGER shadow_contact_delete_trigger AFTER DELETE ON civicrm_contact
    FOR EACH ROW BEGIN
        DELETE FROM shadow_contact WHERE contact_id=OLD.id;
    END
|


-- -----------------------------
-- Triggers for shadow address
-- -----------------------------
DROP TRIGGER IF EXISTS shadow_address_insert_trigger |
CREATE TRIGGER shadow_address_insert_trigger AFTER INSERT ON civicrm_address
    FOR EACH ROW BEGIN
        DECLARE norm_street_address VARCHAR(255);
        DECLARE norm_postal_code VARCHAR(255);
        DECLARE norm_city VARCHAR(255);

        SET norm_street_address = BB_NORMALIZE_ADDR(NEW.street_address);
        SET norm_postal_code = IFNULL(NEW.postal_code,'');
        SET norm_city = IFNULL(NEW.city,'');

        INSERT INTO shadow_address (address_id, contact_id, street_address, postal_code, city) VALUES (NEW.id, NEW.contact_id, norm_street_address, norm_postal_code, norm_city) ON DUPLICATE KEY UPDATE street_address=norm_street_address, postal_code=norm_postal_code, city=norm_city;
    END
|

DROP TRIGGER IF EXISTS shadow_address_update_trigger |
CREATE TRIGGER shadow_address_update_trigger AFTER UPDATE ON civicrm_address
    FOR EACH ROW BEGIN
        DECLARE norm_street_address VARCHAR(255);
        DECLARE norm_postal_code VARCHAR(255);
        DECLARE norm_city VARCHAR(255);

        SET norm_street_address = BB_NORMALIZE_ADDR(NEW.street_address);
        SET norm_postal_code = IFNULL(NEW.postal_code,'');
        SET norm_city = IFNULL(NEW.city,'');

        INSERT INTO shadow_address (address_id, contact_id, street_address, postal_code, city) VALUES (NEW.id, NEW.contact_id, norm_street_address, norm_postal_code, norm_city) ON DUPLICATE KEY UPDATE street_address=norm_street_address, postal_code=norm_postal_code, city=norm_city;
    END
|


DROP TRIGGER IF EXISTS shadow_address_delete_trigger |
CREATE TRIGGER shadow_address_delete_trigger AFTER DELETE ON civicrm_address
    FOR EACH ROW BEGIN
       DELETE FROM shadow_address WHERE address_id=OLD.id;
    END
|


DELIMITER ;