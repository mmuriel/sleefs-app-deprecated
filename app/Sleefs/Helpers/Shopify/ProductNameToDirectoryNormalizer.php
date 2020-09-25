<?php

namespace Sleefs\Helpers\Shopify;

class ProductNameToDirectoryNormalizer{

	/**
    *
    *    This method replaces invalid chars in product name to create a directory name
    *    @param String $productName 
    *    @param String $regexp The regular expression to search for invalid chars
    *    @param String $replacement The regular expression, or just the string to replace to invalid chars, default ""
    *
    *    @return String $normalizedProductName
    *
    */
    public function normalizeProductName ($productName, $regexp = "/[^a-zA-Z0-9\ \-]/",$replacement = ""){

        $normalizedProductName = '';
        $normalizedProductName = preg_replace($regexp,$replacement,$productName);
        return $normalizedProductName;
    }

}