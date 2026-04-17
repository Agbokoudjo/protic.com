<?php

declare(strict_types=1);

/*
 * This file is part of the project by AGBOKOUDJO Franck.
 *
 * (c) AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 * Phone: +229 01 67 25 18 86
 * LinkedIn: https://www.linkedin.com/in/internationales-web-apps-services-120520193/
 * Github: https://github.com/Agbokoudjo/
 * Company: INTERNATIONALES WEB APPS & SERVICES
 *
 * For more information, please feel free to contact the author.
 */

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;

/**
 * Validateur avancé de requêtes navigateur.
 *
 * Détecte et bloque :
 * - curl, wget, python-requests, httpie, postman
 * - Outils de pentest (Burp, ZAProxy, sqlmap, nikto, etc.)
 * - Bots et crawlers
 * - Simulations de navigateur (headers incohérents)
 *
 * Compatible avec :
 * - FrankenPHP (HTTP/2, worker mode)
 * - PHP 8.5 strict_types
 * - Symfony 7.x
 *
 * @author AGBOKOUDJO Franck <internationaleswebservices@gmail.com>
 */
final class BrowserRequestValidator
{
    /**
     * User-Agents des outils automatisés à bloquer.
     *
     * @var list<non-empty-string>
     */
    private const BLOCKED_USER_AGENTS = [
        // Outils de ligne de commande
        'curl',
        'wget',
        'python-requests',
        'httpie',
        'postman',
        'insomnia',
        'paw/',

        // Outils de pentest
        'burpsuite',
        'burp',
        'zaproxy',
        'owasp',
        'nmap',
        'masscan',
        'sqlmap',
        'nikto',
        'nessus',
        'metasploit',
        'wpscan',
        'dirbuster',
        'gobuster',
        'nuclei',
        'hydra',
        'ffuf',

        // Bots et crawlers (hors navigateurs)
        'bot/',
        'crawler',
        'spider',
        'scraper',
        'slurp',
        'googlebot',
        'bingbot',
        'yandexbot',
        'semrushbot',
        'ahrefsbot',
        'mj12bot',
        'dotbot',
        'petalbot',
        'bytespider',

        // Runtimes et frameworks HTTP
        'java/',
        'python/',
        'python-urllib',
        'ruby',
        'perl',
        'node.js',
        'node-fetch',
        'axios/',
        'go-http-client',
        'okhttp',
        'zgrab',
        'golang',
        'libwww-perl',
        'php/',
        'guzzlehttp',
    ];

    /**
     * Headers obligatoires présents dans toute requête d'un vrai navigateur.
     *
     * NOTE : Sec-Fetch-Site retiré volontairement :
     * - Safari < 16.4 ne l'envoie pas
     * - Les navigations directes (barre d'adresse, bookmark, email) ne l'envoient pas
     * - FrankenPHP / certains proxys peuvent le filtrer
     *
     * @var list<non-empty-string>
     */
    private const REQUIRED_BROWSER_HEADERS = [
        'Accept',
        'Accept-Language',
        'Accept-Encoding',
        'User-Agent',
    ];

    /**
     * IPs de confiance exemptées de toute validation.
     * Ajouter ici l'IP de ton monitoring, load balancer, CI/CD.
     *
     * @var list<string>
     */
    private const TRUSTED_IPS = [
        '127.0.0.1',
        '::1',
        '187.124.166.48'
        // Exemple : '185.199.108.0', // GitHub Actions
    ];

    /**
     * Valide si la requête provient d'un navigateur réel.
     *
     * @return bool true = requête valide, false = requête suspecte/à bloquer
     */
    public function isValidBrowserRequest(Request $request): bool
    {
        // 0. IPs de confiance → toujours autorisées
        if ($this->isTrustedIp($request)) {
            return true;
        }

        // 1. User-Agent valide et non blacklisté
        if (!$this->hasValidUserAgent($request)) {
            return false;
        }

        // 2. Headers obligatoires présents
        if (!$this->hasRequiredBrowserHeaders($request)) {
            return false;
        }

        // 3. Cohérence Accept-Language
        if (!$this->isUserAgentLanguageConsistent($request)) {
            return false;
        }

        // 4. Cohérence Accept-Encoding
        if (!$this->isUserAgentEncodingConsistent($request)) {
            return false;
        }

        // 5. Caractéristiques HTTP de la requête (Accept, Referer/Origin, etc.)
        if (!$this->hasValidBrowserCharacteristics($request)) {
            return false;
        }

        // 6. Détection de simulation (headers Sec-Fetch-* incohérents, etc.)
        if ($this->isSimulatedBrowser($request)) {
            return false;
        }

        return true;
    }

    /**
     * Retourne un code court identifiant la raison du blocage (usage interne/logs).
     */
    public function getBlockReasonCode(Request $request): string
    {
        if ($this->isTrustedIp($request)) {
            return 'TRUSTED_IP'; // Ne devrait jamais arriver ici
        }
        if (!$this->hasValidUserAgent($request)) {
            return 'INVALID_USER_AGENT';
        }
        if (!$this->hasRequiredBrowserHeaders($request)) {
            return 'MISSING_REQUIRED_HEADERS';
        }
        if (!$this->isUserAgentLanguageConsistent($request)) {
            return 'ACCEPT_LANGUAGE_INVALID';
        }
        if (!$this->isUserAgentEncodingConsistent($request)) {
            return 'ACCEPT_ENCODING_INVALID';
        }
        if (!$this->hasValidBrowserCharacteristics($request)) {
            return 'INVALID_REQUEST_CHARACTERISTICS';
        }
        if ($this->isSimulatedBrowser($request)) {
            return 'SIMULATED_BROWSER_DETECTED';
        }

        return 'UNKNOWN';
    }

    /**
     * Retourne un message générique destiné au client (sans révéler les détails).
     */
    public function getBlockReasonMessage(Request $request): string
    {
        $ua = $request->headers->get('User-Agent', '');

        if (empty($ua)) {
            return 'Requête rejetée : User-Agent manquant. Accès réservé aux navigateurs.';
        }

        if (!$this->hasValidUserAgent($request)) {
            return sprintf(
                'Requête rejetée : User-Agent "%s" non autorisé. Accès réservé aux navigateurs.',
                mb_substr($ua, 0, 80) // Tronquer pour éviter les injections dans les logs
            );
        }

        if (!$this->hasRequiredBrowserHeaders($request)) {
            return 'Requête rejetée : En-têtes obligatoires manquants. Accès réservé aux navigateurs.';
        }

        if ($this->isSimulatedBrowser($request)) {
            return 'Simulation de navigateur détectée. Accès refusé.';
        }

        return 'Requête rejetée : Caractéristiques de requête invalides. Accès réservé aux navigateurs.';
    }

    /**
     * Vérifie si l'IP est dans la liste de confiance.
     */
    private function isTrustedIp(Request $request): bool
    {
        $ip = $request->getClientIp();

        return $ip !== null && in_array($ip, self::TRUSTED_IPS, true);
    }

    /**
     * Vérifie que le User-Agent est celui d'un navigateur connu et non blacklisté.
     */
    private function hasValidUserAgent(Request $request): bool
    {
        $userAgent = $request->headers->get('User-Agent', '');

        if (empty($userAgent)) {
            return false;
        }

        $uaLower = strtolower($userAgent);

        foreach (self::BLOCKED_USER_AGENTS as $blocked) {
            if (str_contains($uaLower, strtolower($blocked))) {
                return false;
            }
        }

        // Doit correspondre à un moteur de navigateur connu
        return (bool) preg_match(
            '/\b(chrome|chromium|firefox|safari|edg(e|\/)|opera|opr|trident|gecko)\b/i',
            $userAgent
        );
    }

    /**
     * Vérifie la présence des headers minimaux d'un navigateur.
     */
    private function hasRequiredBrowserHeaders(Request $request): bool
    {
        foreach (self::REQUIRED_BROWSER_HEADERS as $header) {
            if (!$request->headers->has($header)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie la cohérence de l'Accept-Language.
     *
     * Format BCP 47 supporté : "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7"
     * Supporte aussi : zh-Hans, zh-Hant-TW, q=1, q=1.0, q=0.999
     *
     * CORRECTION v2 : bug $lanages corrigé + regex BCP 47 complète
     */
    private function isUserAgentLanguageConsistent(Request $request): bool
    {
        $acceptLanguage = $request->headers->get('Accept-Language', '');

        if (empty($acceptLanguage)) {
            return false;
        }

        // Wildcards comme "*" sont valides (rare mais légal)
        if ($acceptLanguage === '*') {
            return true;
        }

        $languages = explode(',', $acceptLanguage);

        foreach ($languages as $lang) {
            $lang = trim($lang);

            if (empty($lang)) {
                continue;
            }

            // BCP 47 complet :
            // - Langue : 1-8 caractères alpha (fr, zh, en, etc.)
            // - Script optionnel : 4 caractères alpha (Hans, Hant, Latn)
            // - Région optionnelle : 2 lettres majuscules ou 3 chiffres
            // - Variante/extension optionnelle
            // - Qualité optionnelle : ;q= suivi de 0.xxx ou 1 ou 1.0
            if (
                !preg_match(
                    '/^[a-zA-Z]{1,8}'                       // Langue principale
                    . '(-[a-zA-Z]{4})?'                     // Script optionnel (Hans, Latn…)
                    . '(-([a-zA-Z]{2}|\d{3}))?'             // Région optionnelle (FR, 419…)
                    . '(-[a-zA-Z0-9]{5,8})*'                // Variantes
                    . '(\s*;\s*q=(0(\.\d{1,3})?|1(\.0{1,3})?))?'  // Qualité ;q=
                    . '$/',
                    $lang
                )
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Vérifie la cohérence de l'Accept-Encoding.
     *
     * Les vrais navigateurs envoient toujours au moins gzip ou deflate.
     */
    private function isUserAgentEncodingConsistent(Request $request): bool
    {
        $acceptEncoding = $request->headers->get('Accept-Encoding', '');

        if (empty($acceptEncoding)) {
            return false;
        }

        $hasGzip    = stripos($acceptEncoding, 'gzip')    !== false;
        $hasDeflate = stripos($acceptEncoding, 'deflate') !== false;
        $hasBr      = stripos($acceptEncoding, 'br')      !== false;
        $hasZstd    = stripos($acceptEncoding, 'zstd')    !== false;

        return $hasGzip || $hasDeflate || $hasBr || $hasZstd;
    }

    /**
     * Vérifie les caractéristiques HTTP d'une requête de navigateur.
     *
     * CORRECTION v2 :
     * - Les appels fetch() natifs (React, Axios sans config) ne définissent PAS
     *   X-Requested-With → isXmlHttpRequest() retourne false pour eux.
     *   On détecte aussi les routes API pour adapter la règle Accept.
     * - POST/PUT/DELETE/PATCH sans Referer ni Origin = suspect sauf webhook/callback.
     */
    private function hasValidBrowserCharacteristics(Request $request): bool
    {
        $accept = $request->headers->get('Accept', '');
        $path   = $request->getPathInfo();

        $isXhr      = $request->isXmlHttpRequest();                  // X-Requested-With: XMLHttpRequest
        $isApiPath  = str_starts_with($path, '/api/');               // Route API Platform
        $hasReferer = $request->headers->has('Referer');
        $hasOrigin  = $request->headers->has('Origin');

        // Pour les requêtes navigateur classiques (non-XHR, non-API),
        // l'Accept doit contenir text/html
        if (!$isXhr && !$isApiPath) {
            if (stripos($accept, 'text/html') === false) {
                return false;
            }
        }

        // Pour les requêtes API (fetch, Axios), Accept doit contenir
        // application/json ou */* (mais pas être complètement vide)
        if ($isApiPath && !$isXhr) {
            $validApiAccept = stripos($accept, 'application/json') !== false
                || stripos($accept, 'application/ld+json') !== false
                || stripos($accept, '*/*') !== false;

            if (!$validApiAccept) {
                return false;
            }
        }

        // Les mutations (POST, PUT, PATCH, DELETE) sans Referer ni Origin sont suspectes.
        // EXCEPTION : routes API Platform (gérées séparément par le token JWT/CSRF).
        if (
            in_array($request->getMethod(), ['POST', 'PUT', 'PATCH', 'DELETE', 'CONNECT'], true)
            && !$isApiPath
            && !$hasReferer
            && !$hasOrigin
        ) {
            return false;
        }

        return true;
    }

    /**
     * Détecte les simulations de navigateur (curl avec UA spoofé, etc.).
     *
     * Indicateurs analysés :
     * - Présence/cohérence des headers Sec-Fetch-*
     * - Cookies absents sur une requête POST non-GET
     *
     * CORRECTION v2 : la méthode ne retourne plus true par accident
     * à cause de hasValidHeaderOrder() qui retournait toujours false.
     */
    private function isSimulatedBrowser(Request $request): bool
    {
        $secFetchSite = $request->headers->get('Sec-Fetch-Site');
        $secFetchMode = $request->headers->get('Sec-Fetch-Mode');
        $secFetchDest = $request->headers->get('Sec-Fetch-Dest');

        $hasFetchHeaders = $secFetchSite !== null
            && $secFetchMode !== null
            && $secFetchDest !== null;

        // Si les headers Sec-Fetch-* sont présents, vérifier leur cohérence
        if ($hasFetchHeaders) {
            if (!$this->areSecFetchHeadersCoherent($secFetchSite, $secFetchMode, $secFetchDest)) {
                return true; // Incohérence = simulation probable
            }
        }

        // POST sans cookie sur une route non-API = suspect
        // (les vrais navigateurs ont au moins le cookie de session après la première visite)
        $isApiPath = str_starts_with($request->getPathInfo(), '/api/');
        if (
            $request->cookies->count() === 0
            && $request->getMethod() === 'POST'
            && !$isApiPath
        ) {
            // Vérifier que ce n'est pas la toute première soumission de formulaire
            $hasReferer = $request->headers->has('Referer');
            $hasOrigin  = $request->headers->has('Origin');

            if (!$hasReferer && !$hasOrigin) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie la cohérence entre les headers Sec-Fetch-*.
     *
     * Combinaisons légitimes connues :
     * - navigate + document  : navigation classique
     * - cors + empty         : fetch() vers une API
     * - same-origin + cors   : fetch() même domaine
     * - no-cors + image/script/style : ressource cross-origin
     */
    private function areSecFetchHeadersCoherent(
        string $site,
        string $mode,
        string $dest
    ): bool {
        // Valeurs légales définies par la spec W3C
        $validSites = ['cross-site', 'same-origin', 'same-site', 'none'];
        $validModes = ['cors', 'navigate', 'no-cors', 'same-origin', 'websocket'];
        $validDests = [
            'audio', 'audioworklet', 'document', 'embed', 'empty',
            'font', 'frame', 'iframe', 'image', 'manifest', 'object',
            'paintworklet', 'report', 'script', 'serviceworker',
            'sharedworker', 'style', 'track', 'video', 'worker', 'xslt',
        ];

        if (
            !in_array($site, $validSites, true)
            || !in_array($mode, $validModes, true)
            || !in_array($dest, $validDests, true)
        ) {
            return false; // Valeur hors spec = simulation
        }

        // Incohérence structurelle : navigate doit avoir dest=document ou iframe/frame
        if ($mode === 'navigate' && !in_array($dest, ['document', 'iframe', 'frame', 'embed', 'object'], true)) {
            return false;
        }

        // cors + document est incohérent (fetch() ne navigue pas vers un document)
        if ($mode === 'cors' && $dest === 'document') {
            return false;
        }

        return true;
    }
}
