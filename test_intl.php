<?php
if (extension_loaded('intl')) {
    echo "intl extension is loaded.\n";
    $fmt = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
    echo $fmt->formatCurrency(1234.56, 'USD') . "\n";
} else {
    echo "intl extension is NOT loaded.\n";
}
