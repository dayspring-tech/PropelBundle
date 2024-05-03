<?php

function autoload_propel_aliases($className) {
    if (str_starts_with((string) $className, 'Propel\PropelBundle')) {
        class_alias(str_replace('Propel\PropelBundle', 'Propel\Bundle\PropelBundle', (string) $className), $className);
    }
}

spl_autoload_register('autoload_propel_aliases', true, true);
