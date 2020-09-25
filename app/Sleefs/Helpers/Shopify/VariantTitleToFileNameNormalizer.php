<?php

namespace Sleefs\Helpers\Shopify;

class VariantTitleToFileNameNormalizer{

	/**
    *
    *    This method replaces invalid chars in variant title to create a valid file name
    *    @param String $variantTitle 
    *    @param String $regexp The regular expression to search for invalid chars
    *    @param String $replacement The regular expression, or just the string to replace to invalid chars, default ""
    *
    *    @return String $normalizedVariantTitle
    *
    */
    public function normalizeVariantTitle ($variantTitle, $regexp = "/[^a-zA-Z0-9\ \-]/",$replacement = ""){

        $normalizedVariantTitle = '';
        $normalizedVariantTitle = preg_replace($regexp,$replacement,$variantTitle);
        return $normalizedVariantTitle;
    }

}