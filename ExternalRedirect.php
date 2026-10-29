<?php
/**
 * ExternalRedirect - MediaWiki extension to allow redirects to external sites.
 * Copyright (C) 2013-2022 Davis Mosenkovs
 * SPDX-License-Identifier: GPL-2.0-or-later
 */

use MediaWiki\Output\OutputPage;
use MediaWiki\Parser\Parser;
use MediaWiki\Parser\ParserOutput;
use MediaWiki\Skin\Skin;

if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

/**
 * Hooks and parser-function implementation for ExternalRedirect.
 * Loaded through extension.json's AutoloadClasses.
 */
class ExternalRedirectHooks {
    private const EXTENSION_DATA_KEY = 'ExternalRedirect-target-url';

    /** Register {{#externalredirect:URL}}. */
    public static function onParserFirstCallInit( Parser $parser ): bool {
        $parser->setFunctionHook( 'externalredirect', [ self::class, 'render' ] );
        return true;
    }

    /**
     * Validate a target and store it for the output hook to redirect later.
     * Redirecting directly from a parser callback is too early/unreliable.
     */
    public static function render( Parser $parser, string $url = '' ): string {
        global $wgExternalRedirectNsIDs, $wgExternalRedirectPages;
        global $wgExternalRedirectURLRegex, $wgExternalRedirectDeniedShowURL;

        $parserOutput = $parser->getOutput();
        $parserOutput->updateCacheExpiry( 0 );
        $title = $parser->getTitle();

        $parts = parse_url( $url );
        $validUrl = is_array( $parts )
            && isset( $parts['scheme'], $parts['host'] )
            && in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true )
            && filter_var( $url, FILTER_VALIDATE_URL ) !== false
            && !preg_match( '/[\x00-\x1F\x7F]/', $url );

        if ( !$validUrl ) {
            return wfMessage( 'externalredirect-invalidurl' )->text();
        }

        $pageAllowed = $title && (
            in_array( $title->getNamespace(), (array)$wgExternalRedirectNsIDs, true )
            || in_array( $title->getPrefixedText(), (array)$wgExternalRedirectPages, true )
        );

        $urlAllowed = true;
        if ( $wgExternalRedirectURLRegex !== '' ) {
            $urlAllowed = @preg_match( $wgExternalRedirectURLRegex, $url ) === 1;
        }

        if ( $pageAllowed && $urlAllowed ) {
            // This data is carried with ParserOutput to the response phase.
            $parserOutput->setExtensionData( self::EXTENSION_DATA_KEY, $url );
            return '';
        }

        $message = wfMessage( 'externalredirect-denied' )->text();
        if ( $wgExternalRedirectDeniedShowURL ) {
            $message .= ' ' . wfMessage( 'externalredirect-denied-url', $url )->text();
        }
        return $message;
    }

    /** Perform the redirect after parser output is attached to the response. */
    public static function onOutputPageParserOutput( OutputPage $out, ParserOutput $parserOutput ): void {
        $url = $parserOutput->getExtensionData( self::EXTENSION_DATA_KEY );
        if ( is_string( $url ) && $url !== '' ) {
            $out->redirect( $url, '302' );
        }
    }
}
