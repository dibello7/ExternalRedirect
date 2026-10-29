# ExternalRedirect for MediaWiki 1.46

## Install
1. Copy this directory to `extensions/ExternalRedirect`.
2. Add `wfLoadExtension( 'ExternalRedirect' );` to `LocalSettings.php`.
3. Configure allowed namespaces/pages and optionally a strict URL allowlist regex.

```php
$wgExternalRedirectNsIDs = [ NS_MAIN ];
$wgExternalRedirectPages = [ 'Project:ExternalLink' ];
$wgExternalRedirectURLRegex = '~^https://(www\\.)?example\\.com(?:/|$)~i';
$wgExternalRedirectDeniedShowURL = false;
```

Use on an allowed wiki page as:

```wikitext
{{#externalredirect:https://example.com/}}
```

Do not use `{{externalredirect:...}}` (without `#`) or wiki-link syntax around the URL.

## Behavior and security
- Requires MediaWiki 1.46 or newer.
- Accepts absolute HTTP and HTTPS URLs and rejects control characters.
- Redirects are disabled unless the current namespace or fully prefixed page name is allowlisted.
- If a URL regex is configured, the URL must match it.
- Redirects use HTTP 302 and are initiated in `OutputPageParserOutput`, after parsing, rather than sending a PHP `Location` header from the parser callback.
- Do not allow untrusted users to edit redirect-enabled pages. Prefer a strict URL regex allowlist.
- After replacing the extension files, purge the target page and test on a staging wiki.
